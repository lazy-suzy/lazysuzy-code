export function makeMultiCarousel(slidesShow = 6, slidesScroll = 6) {
    $('.responsive:not(.slick-slider)').slick({
        infinite: false,
        speed: 300,
        slidesToShow: slidesShow,
        slidesToScroll: slidesScroll,
        arrows: true,
        // centerMode: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });
}
