(function ($) {
    'use strict';

    /*=============================================
	=    		 Preloader			      =
=============================================*/
    $('#preloader').delay(0).fadeOut();
    // }
    var loader = document.getElementById('preloader');
    window.addEventListener('load', function () {
        loader.style.display = 'none';
    });

    /*=============================================
	=    		 Wow Active  	         =
=============================================*/
    function wowAnimation() {
        var wow = new WOW({
            boxClass: 'wow',
            animateClass: 'animated',
            offset: 0,
            mobile: false,
            live: true,
        });
        wow.init();
    }

    /*=============================================
	=    		Mobile Menu			      =
=============================================*/

    //SubMenu Dropdown Toggle
    if ($('.menu-area li.menu-item-has-children ul').length) {
        $('.menu-area .navigation li.menu-item-has-children').append('<div class="dropdown-btn"><span class="fas fa-angle-down"></span></div>');
    }
    //Mobile Nav Hide Show
    if ($('.mobile-menu').length) {
        var mobileMenuContent = $('.menu-area .main-menu').html();
        $('.mobile-menu .menu-box .menu-outer').append(mobileMenuContent);

        //Dropdown Button
        $('.mobile-menu li.menu-item-has-children .dropdown-btn').on('click', function () {
            $(this).toggleClass('open');
            $(this).prev('ul').slideToggle(300);
        });
        //Menu Toggle Btn
        $('.mobile-nav-toggler').on('click', function () {
            $('body').addClass('mobile-menu-visible');
        });

        //Menu Toggle Btn
        $('.menu-backdrop, .mobile-menu .close-btn').on('click', function () {
            $('body').removeClass('mobile-menu-visible');
        });
    }

    /*=============================================
	=     Menu sticky & Scroll to top      =
=============================================*/
    $(window).on('scroll', function () {
        var scroll = $(window).scrollTop();
        if (scroll < 245) {
            $('#sticky-header').removeClass('sticky-menu');
            $('.scroll-to-target').removeClass('open');
        } else {
            $('#sticky-header').addClass('sticky-menu');
            $('.scroll-to-target').addClass('open');
        }
    });

    /*=============================================
	=    		 Scroll Up  	         =
=============================================*/
    if ($('.scroll-to-target').length) {
        $('.scroll-to-target').on('click', function () {
            var target = $(this).attr('data-target');
            // animate
            $('html, body').animate(
                {
                    scrollTop: $(target).offset().top,
                },
                1000
            );
        });
    }

    //====== Progress Bar
    if ($('.count-bar').length) {
        $('.count-bar').appear(
            function () {
                var el = $(this);
                var percent = el.data('percent');
                $(el).css('width', percent).addClass('counted');
            },
            {
                accY: -50,
            }
        );
    }

    //====== Magnific Popup
    if ($('.play-btn').length) {
        $('.play-btn').magnificPopup({
            type: 'iframe',
            mainClass: 'mfp-fade',
            removalDelay: 160,
            preloader: true,
            fixedContentPos: false,
        });
    }

    /*=============================================
	=    		Odometer Active  	       =
=============================================*/
    $('.odometer').appear(function (e) {
        var odo = $('.odometer');
        odo.each(function () {
            var countNumber = $(this).attr('data-count');
            $(this).html(countNumber);
        });
    });

    /*=============================================
	=    		 		      =
=============================================*/

    $(window).on('load', function () {
        wowAnimation();
        thmSwiperInit();
    });
    ///////////////////////////////////////////////////
    // accordion Js

    $('.accordion-title').click(function () {
        $('.accordion-title').not(this).removeClass('open');
        $('.accordion-title').not(this).next().slideUp(300);
        $(this).toggleClass('open');
        $(this).next().slideToggle(300);
    });
    ///////////////////////////////////////////////////
    // hero-slider Js
    $('.hero-slider').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: false,
        margin: 0,
        items: 4,
        nav: true,
        navText: ["<i class='flaticon-left-arrow'></i>", "<i class='flaticon-right-arrow'></i>"],
        dots: true,
        dotsEach: 1,
        animateOut: 'slideOutLeft',
        animateIn: 'slideInRight',
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 1,
            },
            992: {
                items: 1,
            },
            1200: {
                items: 1,
            },
        },
    });

    ///////////////////////////////////////////////////
    // project-slider Js
    $('.project-slider').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: false,
        margin: 30,
        items: 4,
        nav: true,
        navText: ["<i class='flaticon-left-arrow'></i>", "<i class='flaticon-right-arrow'></i>"],
        dots: true,
        dotsEach: 1,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 2,
            },
            992: {
                items: 3,
            },
            1200: {
                items: 3,
            },
        },
    });
    ///////////////////////////////////////////////////
    // project-slider2 Js
    $('.project-slider2').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: false,
        margin: 30,
        items: 4,
        nav: true,
        navText: ["<i class='flaticon-left-arrow'></i>", "<i class='flaticon-right-arrow'></i>"],
        dots: false,
        dotsEach: 1,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 2,
            },
            992: {
                items: 3,
            },
            1200: {
                items: 3,
            },
        },
    });

    ///////////////////////////////////////////////////
    // brand-slider Js
    $('.brand-slider').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: true,
        autoplayTimeout: 2000,
        autoplaySpeed: 800,
        margin: 30,
        items: 4,
        nav: false,
        dots: true,
        dotsEach: 1,
        responsive: {
            0: {
                items: 2,
            },
            767: {
                items: 3,
            },
            992: {
                items: 5,
            },
            1200: {
                items: 5,
            },
        },
    });
    ///////////////////////////////////////////////////
    // testimonial-slider Js
    $('.testimonial-slider').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: true,
        margin: 30,
        items: 4,
        nav: false,
        dots: true,
        dotsEach: 1,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 2,
            },
            992: {
                items: 2,
            },
            1200: {
                items: 3,
            },
        },
    });
    ///////////////////////////////////////////////////
    // testimonial-slider2 Js
    $('.testimonial-slider2').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: true,
        margin: 30,
        items: 4,
        nav: true,
        navText: ["<i class='flaticon-left-arrow'></i>", "<i class='flaticon-right-arrow'></i>"],
        dots: false,
        dotsEach: 1,
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 1,
            },
            992: {
                items: 1,
            },
            1200: {
                items: 1,
            },
        },
    });
    ///////////////////////////////////////////////////
    // testimonial-slider3 Js
    $('.testimonial-slider3').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: true,
        margin: 30,
        items: 4,
        nav: false,
        dots: true,
        dotsEach: 1,
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 1,
            },
            992: {
                items: 2,
            },
            1200: {
                items: 2,
            },
        },
    });

    ///////////////////////////////////////////////////
    // team-slider Js
    $('.team-slider').owlCarousel({
        //add owl carousel in activation class
        loop: true,
        autoplay: false,
        margin: 30,
        items: 4,
        nav: true,
        navText: ["<i class='flaticon-left-arrow'></i>", "<i class='flaticon-right-arrow'></i>"],
        dots: false,
        dotsEach: 1,
        responsive: {
            0: {
                items: 1,
            },
            767: {
                items: 2,
            },
            992: {
                items: 3,
            },
            1200: {
                items: 3,
            },
        },
    });

    $(document).ready(function () {
        $('select:not(.ignore)').niceSelect();
    });
})(jQuery);
