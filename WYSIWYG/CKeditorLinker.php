<?php
 
class CKeditorLinker {
 
       public static function addHooks() {
               global $wgHooks;
 
               $wgHooks['ImageBeforeProduceHTML'][] = 'CKeditorLinker::makeImageLink2';
               $wgHooks['LinkerMakeExternalLink'][] = 'CKeditorLinker::makeExternalLink';
               $wgHooks['LinkEnd'][]                = 'CKeditorLinker::linkEnd';
               $wgHooks['LinkBegin'][]              = 'CKeditorLinker::linkBegin';
       }
 
       public static function removeHooks() {
               self::removeHook('ImageBeforeProduceHTML', 'CKeditorLinker::makeImageLink2');
               self::removeHook('LinkerMakeExternalLink', 'CKeditorLinker::makeExternalLink');
               self::removeHook('LinkEnd', 'CKeditorLinker::linkEnd');
               self::removeHook('LinkBegin', 'CKeditorLinker::linkBegin');
       }
 
       private static function removeHook($hookName, $function) {
               global $wgHooks;
               $hook = $wgHooks[$hookName];
               $i = array_search($function, $hook);
               if ($i) {
                       $wgHooks[$hookName] = array_splice($hook, $i, 1);
               }
       }
 
       static function makeImageLink2( $skin, Title $nt, $file, $frameParams = array(), $handlerParams = array(), $time, &$ret ) {
               $orginal = $nt->getText();
               $file = RepoGroup::singleton()->getLocalRepo()->newFile( $nt );
               $found = $file->exists();
 
               if( !empty( $frameParams['alt'] ) && $frameParams['alt'] == 'RTENOTITLE' ){ // 2223
                       $frameParams['alt'] = '';
               }
               if( $found ) {
                       $type = 'image';
                       if ($file->getMediaType() == MEDIATYPE_VIDEO) {
                               $type = 'video';
                       } else if ($file->getMediaType() == MEDIATYPE_AUDIO) {
                               $type = 'audio';
                       }
                       if ($type == 'image') {
                               $url = $file->getUrl();
                       } else {
                               $url = $file->createThumb( 230 );
                       }
               }
               // Shortcuts
               $fp =& $frameParams;
               $hp =& $handlerParams;
                // Clean up parameters
                $page = isset( $hp['page'] ) ? $hp['page'] : false;
 
                if ( $file && !isset( $hp['width'] ) ) {
                        if ( isset( $hp['height'] ) && $file->isVectorized() ) {
                                // If its a vector image, and user only specifies height
                                // we don't want it to be limited by its "normal" width.
                                global $wgSVGMaxSize;
                                $hp['width'] = $wgSVGMaxSize;
                        } else {
                                $hp['width'] = $file->getWidth( $page );
                        }
 
                        if ( isset( $fp['thumbnail'] ) || isset( $fp['framed'] ) || isset( $fp['frameless'] ) || !$hp['width'] ) {
                                global $wgThumbLimits, $wgThumbUpright;
                                if ( !isset( $widthOption ) || !isset( $wgThumbLimits[$widthOption] ) ) {
                                        $widthOption = User::getDefaultOption( 'thumbsize' );
                                }
 
                                // Reduce width for upright images when parameter 'upright' is used
                                if ( isset( $fp['upright'] ) && $fp['upright'] == 0 ) {
                                        $fp['upright'] = $wgThumbUpright;
                                }
                                // For caching health: If width scaled down due to upright parameter, round to full __0 pixel to avoid the creation of a lot of odd thumbs
                                $prefWidth = isset( $fp['upright'] ) ?
                                        round( $wgThumbLimits[$widthOption] * $fp['upright'], -1 ) :
                                        $wgThumbLimits[$widthOption];
 
                                // Use width which is smaller: real image width or user preference width
                                // Unless image is scalable vector.
                                if ( !isset( $hp['height'] ) && ( $hp['width'] <= 0 ||
                                                $prefWidth < $hp['width'] || $file->isVectorized() ) ) {
                                        $hp['width'] = $prefWidth;
                                }
                        }
                }
               if( !isset( $fp['align'] ) ) {
                       $fp['align'] = '';
               }
 
               $ret = '<img ';
 
               if( $found ) {
                        $ret .= "src=\"{$url}\" ";
               } else {
                       $ret .= "_fck_mw_valid=\"false"."\" ";
               }
               $ret .= "_fck_mw_filename=\"{$orginal}\" ";
 
               if( $fp['align'] ) {
                       $ret .= "_fck_mw_location=\"" . strtolower( $fp['align'] ) . "\" ";
               }
               if( !empty( $hp ) ) {
                       if( isset( $hp['width'] ) ) {
                               $ret .= "_fck_mw_width=\"" . $hp['width'] . "\" ";
                               $ret .= "style=\"width: ".$hp['width']."px\" ";
                       }
                       if( isset( $hp['height'] ) ) {
                               $ret .= "_fck_mw_height=\"" . $hp['height'] . "\" ";
                                $ret .= "style=\"height: ".$hp['height']."px\" ";
                       }
               }
               $class = '';
               if( isset( $fp['thumbnail'] ) ) {
                       $ret .= "_fck_mw_type=\"thumb" . "\" ";
                       $class .= 'fck_mw_frame';
               } else if( isset( $fp['border'] ) ) {
                       $ret .= "_fck_mw_type=\"border" . "\" ";
                       $class .= 'fck_mw_border';
               } else if( isset( $fp['framed'] ) ) {
                       $ret .= "_fck_mw_type=\"frame" . "\" ";
                       $class .= 'fck_mw_frame';
               }
 
               if( $fp['align'] == 'right' ) {
                       $class .= ( $class ? ' ': '' ) . 'fck_mw_right';
               } else if( $fp['align'] == 'center' ) {
                       $class .= ( $class ? ' ' : ''  ) . 'fck_mw_center';
               } else if( $fp['align'] == 'left' ) {
                       $class .= ( $class ? ' ': '' ) . 'fck_mw_left';
               } else if( isset( $fp['framed'] ) || isset( $fp['thumbnail'] ) ) {
                       $class .= ( $class ? ' ' : '' ) . 'fck_mw_right';
               }
 
               if( !$found ) {
                       $class .= ( $class ? ' ' : '' ) . 'fck_mw_notfound';
               }
 
               if( isset( $fp['alt'] ) && !empty( $fp['alt'] ) && $fp['alt'] != 'Image:' . $orginal ) {
                       $ret .= "alt=\"" . htmlspecialchars( $fp['alt'] ) . "\" ";
               } else {
                       $ret .= "alt=\"\" ";
               }
 
               if( $class ) {
                       $ret .= "class=\"$class\" ";
               }
        if (isset($fp['no-link']))
            $ret .= 'no-link="1" ';
        if (isset($fp['link-title']) && is_object($fp['link-title']))
            $ret .= 'link="'.htmlentities ($fp['link-title']->getFullText()).'" ';
        if (isset($fp['link-url']))
            $ret .= 'link="'.$fp['link-url'].'" ';
 
               $ret .= '/>';
 
               return false;
       }
 
 
 
       static function makeExternalLink( $url, $text, &$link, &$attribs, $linktype ) {
               $url = htmlspecialchars( $url );
               //$text = htmlspecialchars( $text );//don't remove html tags
               $url = preg_replace( "/^RTECOLON/", ":", $url ); // change 'RTECOLON' => ':'
               $args = '';
               if( $text == 'RTENOTITLE' ){ // 2223
                       $args .= '_fcknotitle="true" ';
                       $text = $url;
               }
               $link= '<a ' . $args . 'href="' . $url . '">' . $text . '</a>';
 
               return false;
       }
 
       function makeColouredLinkObj( $nt, $colour, $text = '', $query = '', $trail = '', $prefix = '' ) {
               return self::makeKnownLinkObj( $nt, $text, $query, $trail, $prefix, '', $style );
       }
 
       function makeKnownLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' , $aprops = '', $style = '' ) {
               wfProfileIn( __METHOD__ );
 
               $args = '';
               if ( !is_object( $nt ) ) {
                       wfProfileOut( __METHOD__ );
                       return $text;
               }
 
               $u = $nt->getFullText();
               $u = rawurlencode($u);  // Fix for links containing "
               //#Updating links tables -> #Updating_links_tables
               $u = str_replace( "#" . $nt->getFragment(), $nt->getFragmentForURL(), $u );
 
               if ( $nt->getFragment() != '' ) {
                       if( $nt->getPrefixedDBkey() == '' ) {
                               $u = '';
                               if ( '' == $text ) {
                                       $text = htmlspecialchars( $nt->getFragment() );
                               }
                       }
 
                       /**
                        * See tickets 1386 and 1690 before changing anything
                        */
                       if( $nt->getPartialUrl() == '' ) {
                               $u .= $nt->getFragmentForURL();
                       }
               }
               if ( $text == '' ) {
                       $text = htmlspecialchars( $nt->getPrefixedText() );
               }
 
               if( $nt->getNamespace() == NS_CATEGORY ) {
                       $u = ':' . $u;
               }
 
               list( $inside, $trail ) = Linker::splitTrail( $trail );
               $title = "{$prefix}{$text}{$inside}";
 
               $u = preg_replace( "/^RTECOLON/", ":", $u ); // change 'RTECOLON' => ':'
               if( substr( $text, 0, 10 ) == 'RTENOTITLE' ){ // starts with RTENOTITLE
                       $args .= '_fcknotitle="true" ';
                       $title = rawurldecode($u);
                       $trail = substr( $text, 10 ) . $trail;
               }
 
               $r = "<a {$args}href=\"{$u}\">{$title}</a>{$trail}";
               wfProfileOut( __METHOD__ );
               return $r;
       }
 
       function makeBrokenLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) {
               # Fail gracefully
               if ( !isset( $nt ) ) {
                       # throw new MWException();
                       return "<!-- ERROR -->{$prefix}{$text}{$trail}";
               }
               $args = '';
 
               wfProfileIn( __METHOD__ );
 
               $u = $nt->getFullText();
               $u = rawurlencode($u);  // Fix for links containing "
 
               //#Updating links tables -> #Updating_links_tables
               $u = str_replace( "#" . $nt->getFragment(), $nt->getFragmentForURL(), $u );
 
               if ( '' == $text ) {
                       $text = htmlspecialchars( $nt->getPrefixedText() );
               }
               if( $nt->getNamespace() == NS_CATEGORY ) {
                       $u = ':' . $u;
               }
 
               list( $inside, $trail ) = Linker::splitTrail( $trail );
               $title = "{$prefix}{$text}{$inside}";
 
               $u = preg_replace( "/^RTECOLON/", ":", $u ); // change 'RTECOLON' => ':'
               if( substr( $text, 0, 10 ) == 'RTENOTITLE' ){   // starts with RTENOTITLE
                       $args .= '_fcknotitle="true" ';
                       $title = rawurldecode($u);
                       $trail = substr( $text, 10 ) . $trail;
               }
               $s = "<a {$args}href=\"{$u}\">{$title}</a>{$trail}";
 
               wfProfileOut( __METHOD__ );
               return $s;
       }
 
       static function makeSelfLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) {
               $args = '';
               if ( '' == $text ) {
                       $text = $nt->mDbkeyform;
               }
               list( $inside, $trail ) = Linker::splitTrail( $trail );
               $title = "{$prefix}{$text}";
               if( $text == 'RTENOTITLE' ){ // 2223
                       $args .= '_fcknotitle="true" ';
                       $title = $nt->mDbkeyform;
               }
               return "<a {$args}href=\"".$nt->mDbkeyform."\" class=\"selflink\">{$title}</a>{$inside}{$trail}";
       }
 
       /**
        * Create a direct link to a given uploaded file.
        *
        * @param $title Title object.
        * @param $text  String: pre-sanitized HTML
        * @return string HTML
        *
        * @todo Handle invalid or missing images better.
        */
       static function makeMediaLinkObj( $title, $text = '' ) {
               if( is_null( $title ) ) {
                       ### HOTFIX. Instead of breaking, return empty string.
                       return $text;
               } else {
                       $args = '';
                       $orginal = $title->getPartialURL();
                       $img = wfFindFile( $title );
                       if( $img ) {
                               $url  = $img->getURL();
                               $class = 'internal';
                       } else {
                               $upload = SpecialPage::getTitleFor( 'Upload' );
                               $url = $upload->getLocalUrl( 'wpDestFile=' . urlencode( $title->getDBkey() ) );
                               $class = 'new';
                       }
                       $alt = htmlspecialchars( $title->getText() );
                       if( $text == '' ) {
                               $text = $alt;
                       }
                       $orginal = preg_replace( "/^RTECOLON/", ":", $orginal ); // change 'RTECOLON' => ':'
                       if( $text == 'RTENOTITLE' ){ // 2223
                               $args .= '_fcknotitle="true" ';
                               $text = $orginal;
                               $alt = $orginal;
                       }
                       return "<a href=\"{$orginal}\" class=\"$class\" {$args} _fck_mw_filename=\"{$orginal}\" _fck_mw_type=\"media\" title=\"{$alt}\">{$text}</a>";
               }
       }
 
       static function linkBegin( $skin, Title $target, &$text, array &$attribs, &$query, &$options, &$ret) {
               if ( $target->isExternal() ) {
                       $args = '';
                       $u = $target->getFullURL();
                       $link = $target->getPrefixedURL();
                       if ( '' == $text ) {
                               $text = $target->getPrefixedText();
                       }
                       $style = Linker::getInterwikiLinkAttributes( $link, $text, 'extiw' );
 
                       if( $text == 'RTENOTITLE' ) { // 2223
                               $text = $u = $link;
                               $args .= '_fcknotitle="true" ';
                       }
                       $t = "<a {$args}href=\"{$u}\"{$style}>{$text}</a>";
 
                       wfProfileOut( __METHOD__ );
                       $ret = $t;
                       return false;
               }
 
               return true;
       }
 
       static function linkEnd(  $skin, Title $target, array $options, &$text, array &$attribs, &$ret ) {
 
               if ( in_array('known', $options) ) {
                       $args = '';
                       if ( !is_object( $target ) ) {
                               wfProfileOut( __METHOD__ );
                               return $text;
                       }
 
                       $u = $target->getFullText();
                       $u = rawurlencode($u);  // Fix for links containing "
                       //#Updating links tables -> #Updating_links_tables
                       $u = str_replace( "#" . $target->getFragment(), $target->getFragmentForURL(), $u );
 
                       if ( $target->getFragment() != '' ) {
                               if( $target->getPrefixedDBkey() == '' ) {
                                       $u = '';
                                       if ( '' == $text ) {
                                               $text = htmlspecialchars( $target->getFragment() );
                                       }
                               }
 
                               /**
                                * See tickets 1386 and 1690 before changing anything
                                */
                               if( $target->getPartialUrl() == '' ) {
                                       $u .= $target->getFragmentForURL();
                               }
                       }
                       if ( $text == '' ) {
                               $text = htmlspecialchars( $target->getPrefixedText() );
                       }
 
                       if( $target->getNamespace() == NS_CATEGORY ) {
                               $u = ':' . $u;
                       }
 
                       $u = preg_replace( "/^RTECOLON/", ":", $u ); // change 'RTECOLON' => ':'
                       if( substr( $text, 0, 10 ) == 'RTENOTITLE' ){ // starts with RTENOTITLE
                               $args .= '_fcknotitle="true" ';
                               $text = rawurldecode($u);
                       }
 
                       $r = "<a {$args}href=\"{$u}\">{$text}</a>";
                       wfProfileOut( __METHOD__ );
 
                       $ret = $r;
 
                       return false;
               }
 
 
               if (in_array('broken', $options)) {
                       if ( !isset( $target ) ) {
                               # throw new MWException();
                               return "<!-- ERROR -->{$prefix}{$text}";
                       }
                       $args = '';
 
                       wfProfileIn( __METHOD__ );
 
                       $u = $target->getFullText();
                       $u = rawurlencode($u);  // Fix for links containing "
 
                       //#Updating links tables -> #Updating_links_tables
                       $u = str_replace( "#" . $target->getFragment(), $target->getFragmentForURL(), $u );
 
                       if ( '' == $text ) {
                               $text = htmlspecialchars( $target->getPrefixedText() );
                       }
                       if( $target->getNamespace() == NS_CATEGORY ) {
                               $u = ':' . $u;
                       }
 
                       $u = preg_replace( "/^RTECOLON/", ":", $u ); // change 'RTECOLON' => ':'
                       if( substr( $text, 0, 10 ) == 'RTENOTITLE' ){   // starts with RTENOTITLE
                               $args .= '_fcknotitle="true" ';
                               $text = rawurldecode($u);
                       }
                       $s = "<a {$args}href=\"{$u}\">{$text}</a>";
 
                       wfProfileOut( __METHOD__ );
                       $ret = $s;
                       return false;
               }
 
               return true;
       }
 
}