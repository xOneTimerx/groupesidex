/* Scripts en ligne qu'Oxygen et OxyExtras imprimaient (extraits des pages de référence du 25 sept. 2026
   par tools/extract_inline_js.py). Rejoués par le thème ; jQuery requis. */

/* ── Menu Pro (comportements génériques) ── */
function oxygen_init_pro_menu() {
                jQuery('.oxy-pro-menu-container').each(function(){
                    
                    // dropdowns
                    var menu = jQuery(this),
                        animation = menu.data('oxy-pro-menu-dropdown-animation'),
                        animationDuration = menu.data('oxy-pro-menu-dropdown-animation-duration');
                    
                    jQuery('.sub-menu', menu).attr('data-aos',animation);
                    jQuery('.sub-menu', menu).attr('data-aos-duration',animationDuration*1000);

                    oxygen_offcanvas_menu_init(menu);
                    jQuery(window).resize(function(){
                        oxygen_offcanvas_menu_init(menu);
                    });

                    // let certain CSS rules know menu being initialized
                    // "10" timeout is extra just in case, "0" would be enough
                    setTimeout(function() {menu.addClass('oxy-pro-menu-init');}, 10);
                });
            }

            jQuery(document).ready(oxygen_init_pro_menu);
            document.addEventListener('oxygen-ajax-element-loaded', oxygen_init_pro_menu, false);
            
            let proMenuMouseDown = false;

            jQuery(".oxygen-body")
            .on("mousedown", '.oxy-pro-menu-show-dropdown:not(.oxy-pro-menu-open-container) .menu-item-has-children', function(e) {
                proMenuMouseDown = true;
            })

            .on("mouseup", '.oxy-pro-menu-show-dropdown:not(.oxy-pro-menu-open-container) .menu-item-has-children', function(e) {
                proMenuMouseDown = false;
            })

            .on('mouseenter focusin', '.oxy-pro-menu-show-dropdown:not(.oxy-pro-menu-open-container) .menu-item-has-children', function(e) {
                if( proMenuMouseDown ) return;
                
                var subMenu = jQuery(this).children('.sub-menu');
                subMenu.addClass('aos-animate oxy-pro-menu-dropdown-animating').removeClass('sub-menu-left');

                var duration = jQuery(this).parents('.oxy-pro-menu-container').data('oxy-pro-menu-dropdown-animation-duration');

                setTimeout(function() {subMenu.removeClass('oxy-pro-menu-dropdown-animating')}, duration*1000);

                var offset = subMenu.offset(),
                    width = subMenu.width(),
                    docWidth = jQuery(window).width();

                    if (offset.left+width > docWidth) {
                        subMenu.addClass('sub-menu-left');
                    }
            })
            
            .on('mouseleave focusout', '.oxy-pro-menu-show-dropdown .menu-item-has-children', function( e ) {
                if( jQuery(this).is(':hover') ) return;

                jQuery(this).children('.sub-menu').removeClass('aos-animate');

                var subMenu = jQuery(this).children('.sub-menu');
                //subMenu.addClass('oxy-pro-menu-dropdown-animating-out');

                var duration = jQuery(this).parents('.oxy-pro-menu-container').data('oxy-pro-menu-dropdown-animation-duration');
                setTimeout(function() {subMenu.removeClass('oxy-pro-menu-dropdown-animating-out')}, duration*1000);
            })

            // open icon click
            .on('click', '.oxy-pro-menu-mobile-open-icon', function() {    
                var menu = jQuery(this).parents('.oxy-pro-menu');
                // off canvas
                if (jQuery(this).hasClass('oxy-pro-menu-off-canvas-trigger')) {
                    oxygen_offcanvas_menu_run(menu);
                }
                // regular
                else {
                    menu.addClass('oxy-pro-menu-open');
                    jQuery(this).siblings('.oxy-pro-menu-container').addClass('oxy-pro-menu-open-container');
                    jQuery('body').addClass('oxy-nav-menu-prevent-overflow');
                    jQuery('html').addClass('oxy-nav-menu-prevent-overflow');
                    
                    oxygen_pro_menu_set_static_width(menu);
                }
                // remove animation and collapse
                jQuery('.sub-menu', menu).attr('data-aos','');
                jQuery('.oxy-pro-menu-dropdown-toggle .sub-menu', menu).slideUp(0);
            });

            function oxygen_pro_menu_set_static_width(menu) {
                var menuItemWidth = jQuery(".oxy-pro-menu-list > .menu-item", menu).width();
                jQuery(".oxy-pro-menu-open-container > div:first-child, .oxy-pro-menu-off-canvas-container > div:first-child", menu).width(menuItemWidth);
            }

            function oxygen_pro_menu_unset_static_width(menu) {
                jQuery(".oxy-pro-menu-container > div:first-child", menu).width("");
            }

            // close icon click
            jQuery('body').on('click', '.oxy-pro-menu-mobile-close-icon', function(e) {
                
                var menu = jQuery(this).parents('.oxy-pro-menu');

                menu.removeClass('oxy-pro-menu-open');
                jQuery(this).parents('.oxy-pro-menu-container').removeClass('oxy-pro-menu-open-container');
                jQuery('.oxy-nav-menu-prevent-overflow').removeClass('oxy-nav-menu-prevent-overflow');

                if (jQuery(this).parent('.oxy-pro-menu-container').hasClass('oxy-pro-menu-off-canvas-container')) {
                    oxygen_offcanvas_menu_run(menu);
                }

                oxygen_pro_menu_unset_static_width(menu);
            });

            // dropdown toggle icon click
            jQuery('body').on(
                'touchstart click', 
                '.oxy-pro-menu-dropdown-links-toggle.oxy-pro-menu-off-canvas-container .menu-item-has-children > a > .oxy-pro-menu-dropdown-icon-click-area,'+
                '.oxy-pro-menu-dropdown-links-toggle.oxy-pro-menu-open-container .menu-item-has-children > a > .oxy-pro-menu-dropdown-icon-click-area', 
                function(e) {
                    e.preventDefault();

                    // fix for iOS false triggering submenu clicks
                    jQuery('.sub-menu').css('pointer-events', 'none');
                    setTimeout( function() {
                        jQuery('.sub-menu').css('pointer-events', 'initial');
                    }, 500);

                    // workaround to stop click event from triggering after touchstart
                    if (window.oxygenProMenuIconTouched === true) {
                        window.oxygenProMenuIconTouched = false;
                        return;
                    }
                    if (e.type==='touchstart') {
                        window.oxygenProMenuIconTouched = true;
                    }
                    oxygen_pro_menu_toggle_dropdown(this);
                }
            );

            function oxygen_pro_menu_toggle_dropdown(trigger) {

                var duration = jQuery(trigger).parents('.oxy-pro-menu-container').data('oxy-pro-menu-dropdown-animation-duration');

                jQuery(trigger).closest('.menu-item-has-children').children('.sub-menu').slideToggle({
                    start: function () {
                        jQuery(this).css({
                            display: "flex"
                        })
                    },
                    duration: duration*1000
                });
            }
                    
            // fullscreen menu link click
            var selector = '.oxy-pro-menu-open .menu-item a';
            jQuery('body').on('click', selector, function(event){
                
                if (jQuery(event.target).closest('.oxy-pro-menu-dropdown-icon-click-area').length > 0) {
                    // toggle icon clicked, no need to hide the menu
                    return;
                }
                else if ((jQuery(this).attr("href") === "#" || jQuery(this).closest(".oxy-pro-menu-container").data("entire-parent-toggles-dropdown")) && 
                         jQuery(this).parent().hasClass('menu-item-has-children')) {
                    // empty href don't lead anywhere, treat it as toggle trigger
                    oxygen_pro_menu_toggle_dropdown(event.target);
                    // keep anchor links behavior as is, and prevent regular links from page reload
                    if (jQuery(this).attr("href").indexOf("#")!==0) {
                        return false;
                    }
                }

                // hide the menu and follow the anchor
                if (jQuery(this).attr("href").indexOf("#")===0) {
                    jQuery('.oxy-pro-menu-open').removeClass('oxy-pro-menu-open');
                    jQuery('.oxy-pro-menu-open-container').removeClass('oxy-pro-menu-open-container');
                    jQuery('.oxy-nav-menu-prevent-overflow').removeClass('oxy-nav-menu-prevent-overflow');
                }

            });

            // off-canvas menu link click
            var selector = '.oxy-pro-menu-off-canvas .menu-item a';
            jQuery('body').on('click', selector, function(event){
                if (jQuery(event.target).closest('.oxy-pro-menu-dropdown-icon-click-area').length > 0) {
                    // toggle icon clicked, no need to trigger it 
                    return;
                }
                else if ((jQuery(this).attr("href") === "#" || jQuery(this).closest(".oxy-pro-menu-container").data("entire-parent-toggles-dropdown")) && 
                    jQuery(this).parent().hasClass('menu-item-has-children')) {
                    // empty href don't lead anywhere, treat it as toggle trigger
                    oxygen_pro_menu_toggle_dropdown(event.target);
                    // keep anchor links behavior as is, and prevent regular links from page reload
                    if (jQuery(this).attr("href").indexOf("#")!==0) {
                        return false;
                    }
                }
            });

            // off canvas
            function oxygen_offcanvas_menu_init(menu) {

                // only init off-canvas animation if trigger icon is visible i.e. mobile menu in action
                var offCanvasActive = jQuery(menu).siblings('.oxy-pro-menu-off-canvas-trigger').css('display');
                if (offCanvasActive!=='none') {
                    var animation = menu.data('oxy-pro-menu-off-canvas-animation');
                    setTimeout(function() {menu.attr('data-aos', animation);}, 10);
                }
                else {
                    // remove AOS
                    menu.attr('data-aos', '');
                };
            }
            
            function oxygen_offcanvas_menu_run(menu) {

                var container = menu.find(".oxy-pro-menu-container");
                
                if (!container.attr('data-aos')) {
                    // initialize animation
                    setTimeout(function() {oxygen_offcanvas_menu_toggle(menu, container)}, 0);
                }
                else {
                    oxygen_offcanvas_menu_toggle(menu, container);
                }
            }

            var oxygen_offcanvas_menu_toggle_in_progress = false;

            function oxygen_offcanvas_menu_toggle(menu, container) {

                if (oxygen_offcanvas_menu_toggle_in_progress) {
                    return;
                }

                container.toggleClass('aos-animate');

                if (container.hasClass('oxy-pro-menu-off-canvas-container')) {
                    
                    oxygen_offcanvas_menu_toggle_in_progress = true;
                    
                    var animation = container.data('oxy-pro-menu-off-canvas-animation'),
                        timeout = container.data('aos-duration');

                    if (!animation){
                        timeout = 0;
                    }

                    setTimeout(function() {
                        container.removeClass('oxy-pro-menu-off-canvas-container')
                        menu.removeClass('oxy-pro-menu-off-canvas');
                        oxygen_offcanvas_menu_toggle_in_progress = false;
                    }, timeout);
                }
                else {
                    container.addClass('oxy-pro-menu-off-canvas-container');
                    menu.addClass('oxy-pro-menu-off-canvas');
                    oxygen_pro_menu_set_static_width(menu);
                }
            }

/* ── Menu Pro — ouverture des sous-menus au clic ── */
jQuery('#-pro-menu-166-69 .oxy-pro-menu-show-dropdown .menu-item-has-children > a', 'body').each(function(){
                jQuery(this).append('<div class="oxy-pro-menu-dropdown-icon-click-area"><svg class="oxy-pro-menu-dropdown-icon"><use xlink:href="#FontAwesomeicon-angle-down"></use></svg></div>');
            });
            jQuery('#-pro-menu-166-69 .oxy-pro-menu-show-dropdown .menu-item:not(.menu-item-has-children) > a', 'body').each(function(){
                jQuery(this).append('<div class="oxy-pro-menu-dropdown-icon-click-area"></div>');
            });

/* ── Menu Pro — ouverture des sous-menus au clic ── */
jQuery('#-pro-menu-31-69 .oxy-pro-menu-show-dropdown .menu-item-has-children > a', 'body').each(function(){
                jQuery(this).append('<div class="oxy-pro-menu-dropdown-icon-click-area"><svg class="oxy-pro-menu-dropdown-icon"><use xlink:href="#FontAwesomeicon-angle-down"></use></svg></div>');
            });
            jQuery('#-pro-menu-31-69 .oxy-pro-menu-show-dropdown .menu-item:not(.menu-item-has-children) > a', 'body').each(function(){
                jQuery(this).append('<div class="oxy-pro-menu-dropdown-icon-click-area"></div>');
            });

/* ── En-tête collant ── */
jQuery(document).ready(function() {
				var selector = "#_header-5-69",
					scrollval = parseInt("300");
				if (!scrollval || scrollval < 1) {
											jQuery("body").css("margin-top", jQuery(selector).outerHeight());
						jQuery(selector).addClass("oxy-sticky-header-active");
									}
				else {
					var scrollTopOld = 0;
					jQuery(window).scroll(function() {
						if (!jQuery('body').hasClass('oxy-nav-menu-prevent-overflow')) {
							if (jQuery(this).scrollTop() > scrollval 
																) {
								if (
																		!jQuery(selector).hasClass("oxy-sticky-header-active")) {
									if (jQuery(selector).css('position')!='absolute') {
										jQuery("body").css("margin-top", jQuery(selector).outerHeight());
									}
									jQuery(selector)
										.addClass("oxy-sticky-header-active")
																			.addClass("oxy-sticky-header-fade-in");
																	}
							}
							else {
								jQuery(selector)
									.removeClass("oxy-sticky-header-fade-in")
									.removeClass("oxy-sticky-header-active");
								if (jQuery(selector).css('position')!='absolute') {
									jQuery("body").css("margin-top", "");
								}
							}
							scrollTopOld = jQuery(this).scrollTop();
						}
					})
				}
			});

/* ── Menu de navigation (hamburger) ── */
jQuery(document).ready(function() {
				jQuery('body').on('click', '.oxy-menu-toggle', function() {
					jQuery(this).parent('.oxy-nav-menu').toggleClass('oxy-nav-menu-open');
					jQuery('body').toggleClass('oxy-nav-menu-prevent-overflow');
					jQuery('html').toggleClass('oxy-nav-menu-prevent-overflow');
				});
				var selector = '.oxy-nav-menu-open .menu-item a[href*="#"]';
				jQuery('body').on('click', selector, function(){
					jQuery('.oxy-nav-menu-open').removeClass('oxy-nav-menu-open');
					jQuery('body').removeClass('oxy-nav-menu-prevent-overflow');
					jQuery('html').removeClass('oxy-nav-menu-prevent-overflow');
					jQuery(this).click();
				});
			});

/* ── AOS des lignes verticales ── */
jQuery('.vertical-line').attr({'data-aos-enable': 'true','data-aos': 'slide-down','data-aos-once': 'true',});
	  	AOS.init({
	  		  		  		  		  		  		  				  			})
		
				jQuery('body').addClass('oxygen-aos-enabled');

/* ── Menu coulissant (OxyExtras) ── */
document.addEventListener(
                        "DOMContentLoaded", () => {

                                document.querySelectorAll('.oxy-horizontal-slide-menu_inner .menu-item-has-children > a[href*="#"]').forEach( menuItemWithChild => {
                                    jQuery(menuItemWithChild).contents().unwrap().wrap("<span></span>")
                                });

                                document.addEventListener(
                                    "DOMContentLoaded", () => {
                                        const menu = new MmenuLight(
                                            document.querySelector("#mmenu")
                                        );

                                        const navigator = menu.navigation({
                                            slidingSubmenus: true,
                                            theme: 'dark',
                                            title: 'Menu'
                                        });
                                    }
                                );

                            document.querySelectorAll('.oxy-horizontal-slide-menu_inner').forEach( slideMenu => {

                                new Mmenu( slideMenu, {
                                        offCanvas: {
                                                use: false,
                                        },
                                        navbar: {
                                            add: true,
                                            title: slideMenu.getAttribute('data-navbar-title'),
                                            titleLink: slideMenu.getAttribute('data-navbar-link'),
                                        }, 
                                        slidingSubmenus: true, 
                                        panelNodetype: ["div", "ul", "ol"]
                                        },
                                        {
                                        screenReader: {
                                            closeSubmenu: slideMenu.getAttribute('data-close'),
                                            openSubmenu: slideMenu.getAttribute('data-open'),
                                        }
                                    }

                                )

                                slideMenu.querySelectorAll('.menu-item-has-children > .mm-listitem__btn').forEach( subMenuLink => {
                                    subMenuLink.innerHTML += '<svg class=\"oxy-horizontal-slide-menu_icon\"><use xlink:href=\"#'+ slideMenu.getAttribute('data-icon') +'\"></use></svg>';
                                }); 

                                slideMenu.querySelectorAll('.mm-btn--prev').forEach( backLink => {
                                    backLink.innerHTML += '<svg class=\"oxy-horizontal-slide-menu_icon-prev\"><use xlink:href=\"#'+ slideMenu.getAttribute('data-icon') +'\"></use></svg>';
                                }); 
                                
                            });  

                        }
                    );

/* ── Défilement vers les ancres ── */
jQuery(document).on('click','a[href*="#"]',function(t){if(jQuery(t.target).closest('.wc-tabs').length>0){return}if(jQuery(this).is('[href="#"]')||jQuery(this).is('[href="#0"]')||jQuery(this).is('[href*="replytocom"]')){return};if(location.pathname.replace(/^\//,"")==this.pathname.replace(/^\//,"")&&location.hostname==this.hostname){var e=jQuery(this.hash);(e=e.length?e:jQuery("[name="+this.hash.slice(1)+"]")).length&&(t.preventDefault(),jQuery("html, body").animate({scrollTop:e.offset().top-200},1000))}});

/* ── Accordéon Pro (OxyExtras) ── */
jQuery(document).ready(oxygen_init_accordion);
            function oxygen_init_accordion($) {
                
                let touchEvent = 'click';  

                let extrasAccordion = function ( container ) {
                    
                $(container).find('.oxy-pro-accordion').each(function(){
                    
                    var $accordion = $(this);
                    var disable_sibling = $accordion.find('.oxy-pro-accordion_inner').data('disablesibling');

                    if ( 'manual' === $(this).find('.oxy-pro-accordion_inner').data('type') ) {

                        
                        var $accordion_header = $accordion.find('.oxy-pro-accordion_header');
                        var $accordion_item = $accordion.find('.oxy-pro-accordion_item');
                        var $accordion_body = $accordion.find('.oxy-pro-accordion_body');
                        var $speed = $accordion.find('.oxy-pro-accordion_inner').data('expand');
                        var mediaPlayer = $accordion.parent().children('.oxy-pro-accordion').find('.oxy-pro-media-player vime-player');
                        var accordionID = '#' + $accordion.attr('id');

                        var repeaterFirst = $accordion.find('.oxy-pro-accordion_inner').data('repeater-first')

                        
                        
                        if (true === $accordion.find('.oxy-pro-accordion_inner').data('repeater')) {
                            $accordion.closest('.oxy-dynamic-list').children('.ct-div-block').attr('data-counter', 'true');
                            $accordion.attr('data-counter', 'false');

                            if ( repeaterFirst ) {

                                $accordion.closest('.oxy-dynamic-list > .ct-div-block:first-child').find('.oxy-pro-accordion_item').addClass('active')
                                $accordion.closest('.oxy-dynamic-list > .ct-div-block:first-child').find('.oxy-pro-accordion_item').attr('data-init', 'open')
                                $accordion.closest('.oxy-dynamic-list > .ct-div-block:first-child').find('.oxy-pro-accordion_header').attr('aria-expanded', 'true')
                            }
                        }
                        
                        $accordion_header.on(touchEvent, function() {

                            $accordion_item.toggleClass('active');
                            $accordion_body.slideToggle($speed);
                            $accordion.trigger('extras_pro_accordion:toggle');
                            $accordion_header.attr('aria-expanded', function (i, attr) {
                                                        return attr == 'true' ? 'false' : 'true'
                                                    });
                                                    
                            if (true !== disable_sibling) {

                                /* Sibling */
                                if (false === disable_sibling) {

                                    if (!$accordion.siblings('.oxy-pro-accordion').length && ($accordion.closest('.oxy-dynamic-list').length) ) {
                                        $accordion_item_active_sibling = $accordion.closest('.oxy-dynamic-list > .ct-div-block').siblings('.ct-div-block').find('.oxy-pro-accordion').children('.oxy-pro-accordion_inner[data-type=manual]').children('.oxy-pro-accordion_item.active')
                                    } else {
                                        $accordion_item_active_sibling = $accordion.siblings('.oxy-pro-accordion').children('.oxy-pro-accordion_inner[data-type=manual]').children('.oxy-pro-accordion_item.active');
                                    }

                                } else {  /* Container */
                                    $accordion_item_active_sibling = $(disable_sibling).find('.oxy-pro-accordion').not(accordionID).children('.oxy-pro-accordion_inner[data-type=manual]').children('.oxy-pro-accordion_item.active');
                                }    
                                    
                                $accordion_item_active_sibling.find('.oxy-pro-accordion_body').slideUp($speed);
                                $accordion_item_active_sibling.find('.oxy-pro-accordion_header').attr('aria-expanded', function (i, attr) {
                                                            return attr == 'true' ? 'false' : 'true'
                                                        });

                                $accordion_item_active_sibling.removeClass('active');

                            }

                            $accordion.trigger('extras_pro_accordion:toggle');
                            
                            mediaPlayer.each(function() {
                                $(this)[0].pause();
                            });
                            
                        });
                        
                    } else {
                        
                        var $accordion_item = $accordion.find('.oxy-pro-accordion_item');
                        var $accordion_item_first = $accordion_item.first();
                        var $accordion_first_open = $accordion.children('.oxy-pro-accordion_inner').data('acf');
                        var $speed = $accordion.find('.oxy-pro-accordion_inner').data('expand');
                        
                        
                        if ( 'closed' !== $accordion_first_open ) {
                            
                            $accordion_item_first.addClass('active');
                            $accordion_item_first.children('.oxy-pro-accordion_body').show();
                            $accordion_item_first.children('.oxy-pro-accordion_header').attr('aria-expanded', 'true');
                        }
                        
                        $accordion_item.each(function(){
                            
                            var $item = $(this);
                            var $accordion_header = $item.find('.oxy-pro-accordion_header');
                            var $accordion_body = $item.find('.oxy-pro-accordion_body');
                            
                            $accordion_header.on(touchEvent, function() {
                            
                                $item.toggleClass('active');
                                $accordion_body.slideToggle($speed);
                                $accordion_header.attr('aria-expanded', function (i, attr) {
                                                        return attr == 'true' ? 'false' : 'true'
                                                    });

                                if (true !== disable_sibling) {
                                    $item.siblings('.oxy-pro-accordion_item.active').find('.oxy-pro-accordion_body').slideUp($speed);
                                    $item.siblings('.oxy-pro-accordion_item.active').removeClass('active');
                                    $item.siblings('.oxy-pro-accordion_item').find('.oxy-pro-accordion_header').attr('aria-expanded', 'false');
                                }

                            });
                            
                        });
                        
                        
                    }


                    var $accordionHeaders = $accordion.find('.oxy-pro-accordion_header');
                    var $accordionItems = $accordion.find('.oxy-pro-accordion_item');

                    $accordionHeaders.each(function( index, accordionHeader ) {

                        $accordion_header = $(accordionHeader);

                        $accordion_header.on( "keydown", (e) => {

                            if ('ArrowDown' === e.code ) {
                                e.preventDefault()

                                if ( $accordionItems[(index + 1)] ) {
                                    $($accordionItems[(index + 1)]).find('.oxy-pro-accordion_header').focus()
                                } else {
                                    $($accordionItems[0]).find('.oxy-pro-accordion_header').focus()
                                }

                            } else if ( 'ArrowUp' === e.code ) {
                                e.preventDefault()
                                if ( $accordionItems[(index - 1)] ) {
                                    $($accordionItems[(index - 1)]).find('.oxy-pro-accordion_header').focus()
                                } else {
                                    $($accordionItems[$accordionItems.length - 1]).find('.oxy-pro-accordion_header').focus()
                                }
                            } else if ( 'Home' === e.code ) {
                                e.preventDefault()
                                $($accordionItems[0]).find('.oxy-pro-accordion_header').focus()

                            } else if ( 'End' === e.code ) {
                                e.preventDefault()
                                $($accordionItems[$accordionItems.length - 1]).find('.oxy-pro-accordion_header').focus()
                            }
                        } );
                    })
                    
                });

                

                }
                
                extrasAccordion('body');
                
                // Expose function
                window.doExtrasAccordion = extrasAccordion;
                    
            };

/* ── Onglets Oxygen ── */
function oxygenVSBInitTabs(element) {
				if (element!==undefined) {
					jQuery(element).find('.oxy-tabs-wrapper').addBack('.oxy-tabs-wrapper').each(function(index) {
						jQuery(this).children('.oxy-tabs-wrapper > div').eq(0).trigger('click');
					});
				}
				else {
					jQuery('.oxy-tabs-wrapper').each(function(index) {
						jQuery(this).children('.oxy-tabs-wrapper > div').eq(0).trigger('click');
					});
				}
			}

			jQuery(document).ready(function() {
                let event = new Event('oxygenVSBInitTabsJs');
                document.dispatchEvent(event);
			});

            document.addEventListener("oxygenVSBInitTabsJs",function(){
                oxygenVSBInitTabs();
            },false);
  
			// handle clicks on tabs  
			jQuery("body").on('click', '.oxy-tabs-wrapper > div', function(e) {

			    /* a tab or an element that is a child of a tab has been clicked. prevent any default behavior */
			    //e.preventDefault();
			    
			    /* which tab has been clicked? (e.target might be a child of the tab.) */
			    clicked_tab = jQuery(e.target).closest('.oxy-tabs-wrapper > div');
			    index = clicked_tab.index();  
			    
			    /* which tabs-wrapper is this tab inside? */
			    tabs_wrapper = jQuery(e.target).closest('.oxy-tabs-wrapper');

			    /* what class dp we use to signify an active tob? */
			    class_for_active_tab = tabs_wrapper.attr('data-oxy-tabs-active-tab-class');
			    
			    /* make all the other tabs in this tabs-wrapper inactive */
			    jQuery(tabs_wrapper).children('.oxy-tabs-wrapper > div').removeClass(class_for_active_tab);

			    /* make the clicked tab the active tab */    
			    jQuery(tabs_wrapper).children('.oxy-tabs-wrapper > div').eq(index).addClass(class_for_active_tab);

			    /* which tabs-contents-wrapper is used by these tabs? */
			    tabs_contents_wrapper_id = tabs_wrapper.attr('data-oxy-tabs-contents-wrapper');

			    /* try to grab the correct content wrapper, in case of duplicated ID's */
                $content_wrapper = jQuery(tabs_wrapper).next();
                if( $content_wrapper.attr("id") != tabs_contents_wrapper_id ) $content_wrapper = jQuery( '#' + tabs_contents_wrapper_id );

                $content_tabs = $content_wrapper.children( "div" );

                /* hide all of the content */
                $content_tabs.addClass('oxy-tabs-contents-content-hidden');
			    
			    /* unhide the content corresponding to the active tab*/
                $content_tabs.eq(index).removeClass('oxy-tabs-contents-content-hidden');
			  
			});

/* ── Toggle Oxygen ── */
jQuery(document).ready(function() {
                let event = new Event('oxygenVSBInitToggleJs');
                document.dispatchEvent(event);
			});

            document.addEventListener("oxygenVSBInitToggleJs",function(){
                oxygenVSBInitToggleState();
            },false);

			oxygenVSBInitToggleState = function() {

				jQuery('.oxy-toggle').each(function() {
				
					var initial_state = jQuery(this).attr('data-oxy-toggle-initial-state'),
					   toggle_target = jQuery(this).attr('data-oxy-toggle-target'),
                       active_class = jQuery(this).attr('data-oxy-toggle-active-class');
				
					if (initial_state == 'closed') {
						if (!toggle_target) {
							jQuery(this).next().hide();
						} else {
							jQuery(toggle_target).hide();
						}
						jQuery(this).children('.oxy-expand-collapse-icon').addClass('oxy-eci-collapsed');
                        jQuery(this).removeClass(active_class)
					}
                    else {
                        jQuery(this).addClass(active_class)
                    }
				});
			}

            jQuery("body").on('click', '.oxy-toggle', function() {

                var toggle_target  = jQuery(this).attr('data-oxy-toggle-target'),
                    active_class   = jQuery(this).attr('data-oxy-toggle-active-class');

                jQuery(this).toggleClass(active_class)
                jQuery(this).children('.oxy-expand-collapse-icon').toggleClass('oxy-eci-collapsed');

                if (!toggle_target) {
                    jQuery(this).next().toggle();
                } else {
                    jQuery(toggle_target).toggle();
                }

                // force 3rd party plugins to rerender things inside the toggle
                jQuery(window).trigger('resize');
            });

/* ── Onglets dynamiques (OxyExtras) ── */
jQuery(document).ready(oxygen_dynamic_tabs);
        function oxygen_dynamic_tabs($) {

            let extrasTabs = function ( container ) {
            
                $(container).find('.oxy-dynamic-tabs_inner').each(function(i, dynamicTabs){
                    
                    $(dynamicTabs).skeletabs({
                        autoplay: $(dynamicTabs).data('autoplay'),
                        autoplayInterval: $(dynamicTabs).data('autoplay-int'),
                        breakpoint: $(dynamicTabs).data('breakpoint'),
                        breakpointLayout: $(dynamicTabs).data('mobile-layout'),
                        transitionDuration: $(dynamicTabs).data('duration'),    
                        history: $(dynamicTabs).data('history'),
                        keyboard: $(dynamicTabs).data('keyboard'),
                        panelHeight: $(dynamicTabs).data('panel-height'),
                        pauseOnFocus: $(dynamicTabs).data('pauseonfocus'),
                        pauseOnHover: $(dynamicTabs).data('pauseonhover'), 
                        keyboardAccordion: 'vertical',
                        keyboardTabs: 'horizontal',
                        disabledIndex: null,
                        selectEvent: 'click',
                        slidingAccordion: true,
                        resizeTimeout: 100,    
                        startIndex: 0,
                        },
                        {
                        tabGroup: 'oxy-dynamic-tabs_tab-group',
                        tabItem: 'oxy-dynamic-tabs_tab-item',
                        tab: 'oxy-dynamic-tabs_tab',
                        panelGroup: 'oxy-dynamic-tabs_panel-group',
                        panel: 'oxy-dynamic-tabs_panel',
                        panelHeading: 'oxy-dynamic-tabs_panel-heading',
                        init: 'oxy-dynamic-tabs_init',
                        tabsMode: 'oxy-dynamic-tabs_mode-tabs',
                        accordionMode: 'oxy-dynamic-tabs_mode-accordion',
                        active: 'oxy-dynamic-tabs_active',
                        disabled: 'oxy-dynamic-tabs_disabled',
                        enter: 'oxy-dynamic-tabs_enter',
                        enterActive: 'oxy-dynamic-tabs_enter-active',
                        enterDone: 'oxy-dynamic-tabs_enter-done',
                        leave: 'oxy-dynamic-tabs_leave',
                        leaveActive: 'oxy-dynamic-tabs_leave-active',
                        leaveDone: 'oxy-dynamic-tabs_leave-done',
                    });

                });

            }
                
            extrasTabs('body');
            
            // Expose function
            window.doExtrasTabs = extrasTabs;

        }

/* ── Galeries Oxygen (PhotoSwipe) ── */
document.addEventListener("oxygenVSBInitGalleryJs_gallery-76-1654",function(){
                        if(jQuery('#_gallery-76-1654').photoSwipe) {
                            jQuery('#_gallery-76-1654').photoSwipe('.oxy-gallery-item-contents');
                        }
                    },false);
                    jQuery(document).ready(function() {
                        let event = new Event('oxygenVSBInitGalleryJs_gallery-76-1654');
                        document.dispatchEvent(event);
                    });

/* ── Galeries Oxygen (PhotoSwipe) ── */
document.addEventListener("oxygenVSBInitGalleryJs_gallery-81-120",function(){
                        if(jQuery('#_gallery-81-120').photoSwipe) {
                            jQuery('#_gallery-81-120').photoSwipe('.oxy-gallery-item-contents');
                        }
                    },false);
                    jQuery(document).ready(function() {
                        let event = new Event('oxygenVSBInitGalleryJs_gallery-81-120');
                        document.dispatchEvent(event);
                    });

/* ── Galeries Oxygen (PhotoSwipe) ── */
document.addEventListener("oxygenVSBInitGalleryJs_gallery-12-1584",function(){
                        if(jQuery('#_gallery-12-1584').photoSwipe) {
                            jQuery('#_gallery-12-1584').photoSwipe('.oxy-gallery-item-contents');
                        }
                    },false);
                    jQuery(document).ready(function() {
                        let event = new Event('oxygenVSBInitGalleryJs_gallery-12-1584');
                        document.dispatchEvent(event);
                    });
