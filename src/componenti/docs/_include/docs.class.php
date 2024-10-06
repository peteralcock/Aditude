<?php
/*
	class to show docs
*/

class docs {

	var $gestore;


	function __construct () {
		global $root;
		$this->gestore = $_SERVER["PHP_SELF"];
	}

    function getDocs() {
        global $root, $session, $VERSION_NUMBER;

        if ($session->get("idutente") == "") {
           $menu = "<script>var NOTMENU = true; </script>" . JQUERYINCLUDE;
        } else {
            $menu = JQUERYINCLUDE;
        }
        $html = loadTemplate( $root . "docs/AdAdmindocumentation.html");
        $html = str_replace("<head>", "<head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>" . $menu  . "<script src=\"".$root."src/template/comode.js?ver=".$VERSION_NUMBER."\"></script>",$html);
        $html = str_replace("src=\"images/", "src=\"" . $root . "docs/images/", $html);
        $html = str_replace("</body>", "</div></div>
            <a href=\"#\" id='scrollToTop' class='icon-up-open-big' onclick=\"scrollToTop();\"></a>
            <script>
                function scrollToTop() {
                    // jquery scroll smooth to top
                    $('html, body').animate({
                            scrollTop: 0
                     }, 500);
                }
                $('.corpo.docs a').on('click',function(e){
                    e.preventDefault();
                    if($(this).attr('href').indexOf('#')==0){
                        var hash = $(this).attr('href');
                        hash = hash.replace(/\./g, '\\\.');
                        $('html, body').animate({
                            scrollTop: $(hash).offset().top
                        },500);                        
                    }
                });
            </script>
        
            <style>body {max-width:none!important}
            #scrollToTop {position:fixed;bottom:1rem; left:1rem;background: var(--menu-top0-bg);color: var(--menu-fg)!IMPORTANT;display:block;width:3rem;height:3rem;line-height:3rem;border-radius:50%;text-align:center}
            #scrollToTop:before {line-height:3rem}
            .corpo.docs h1 *, .corpo.docs h1 { background: var(--menu-top0-bg);color: var(--menu-fg)!IMPORTANT;}
            .corpo.docs h1 {    padding: 1rem; margin: 1rem 0;}
            .corpo.docs h1:empty {display:none}
            .corpo.docs span:empty {display:none}
            body,.corpo.docs {background: var(--main-bg-color)!important;padding:0rem!important;margin:0rem!important}
            .thepage {
                background: var(--panel-bg-color);padding:1rem;
                box-shadow: 0 0.5rem 1rem var(--grid-head);
                width:100vw;overflow:hidden;
            }
            .thepage img {width:100%!important;height:auto!important}
            .thepage span {width:auto!important;height:auto!important}
            @media screen and (min-width: 768px) {
                .thepage { padding:4rem; width:100%}
                body { padding:4rem 8rem!important}
                .corpo.docs {width:100%!important}
                .corpo.docs.contract {width:calc( 100% - 17rem)!important}
            }

            p {word-break:break-word;}
            ".($session->get("idutente")=="" ? "#mobiletoggle,#mainmenucontainer {display:none}" : "") ."

    
        
        </style></body>", $html);
        $html = preg_replace("/<body([^>]*)>/", "<body$1><div class='corpo docs'><div class='thepage'>", $html);
        $html = str_replace("src=\"images/", "src=\"" . $root . "docs/images/", $html);
        return $html;
    }

}

?>