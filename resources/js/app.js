require("bootstrap");
require("slick-lightbox");
require("slick-carousel");
require("./components/multi-carousel");
require("./components/custom-selectbox");
require("./components/horizontal-scrollbar");
var md = require("markdown-it")({
    html: true,
    breaks: true
});
function slickLightboxcode() {
    $(".topcateLightBox").slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        mobileFirst: true
    });
    $(".topcateLightBox").slickLightbox({
        itemSelector: "a",
        navigateByKeyboard: true,
        captionPosition: "dynamic",
        layouts: {
            closeButton:
                '<button type="button" class="slick-lightbox-close"></button>'
        }
    });
}
$(document).ready(function() {
    const search = window.location.search.substring(1);
    const queryObject = search
        ? JSON.parse(
              '{"' +
                  decodeURI(search)
                      .replace(/"/g, '\\"')
                      .replace(/&/g, '","')
                      .replace(/=/g, '":"') +
                  '"}'
          )
        : {};

    if (queryObject.error) {
        if (queryObject.error === "login") {
            $("#modalLoginForm").modal("toggle");
        } else {
            $("#modalSignupForm").modal("toggle");
        }
    }
    $("#departmentsNav").on("click", ".dropdown", function(e) {
        console.log("test");
        // e.preventDefault()
        $(this)
            .siblings()
            .removeClass("active");
        $(this).addClass("active");
    });
    $("#searchbarHeader").submit(function(e) {
        callSearch(e, this);
    });
    $(".navbar-toggler").click(function() {
        $("#Sidenavbar").css("width", "300px");
    });
    $(".sb-body").submit(function(e) {
        callSearch(e, this);
    });
    $("#Sidenavbarclose").click(function() {
        $("#Sidenavbar").css("width", "0px");
    });
    $(".arrow").on("click", function(event) {
        $(".arrow-img").toggleClass("rotate");
        $(".arrow-img").toggleClass("rotate-reset");
    });

    $(document).on("click", ".collapsible", function() {
        $(".collapsible").removeClass("active");
        this.classList.toggle("active");
        $(".collapse").hide();
        $(this.getAttribute("data-target")).show();
    });

    function callSearch(e, elm) {
        e.preventDefault();
        window.location.href =
            "/search?query=" +
            $(elm)
                .find("input")
                .val(); //relative to domain
    }

    var $searchIcon = $("#searchIconMobile");

    const DEPT_API = "/api/all-departments";

    $searchIcon.on("click", function(e) {
        if ($(this).attr("id") == "searchIconMobile") {
            $("#searchbarHeader").toggleClass("open");
        }
    });

    $(".user-login-modal").click(function() {
        $("#modalSignupForm").modal("toggle");
    });
    $("#register-modal").click(function() {
        $("#modalSignupForm").modal("toggle");
        $("#modalLoginForm").modal("toggle");
    });
    $(".user-login-modal1").click(function() {
        $("#modalSignupForm").modal("toggle");
        $("#modalLoginForm").modal("toggle");
    });

    $(".wishlist-login-modal").click(function() {
        $("#modalLoginForm").modal();
    });

    $("body").on("mouseover", ".dropdown-submenu", function(e) {
        var self = this;
        $(".dropdown-submenu").each(function() {
            if ($(this).find(".dropdown-menu")[0] != $(self).next("ul")[0]) {
                $(this)
                    .find(".dropdown-menu")
                    .hide();
            }
        });
        $(this)
            .find("ul")
            .toggle();
        if (!isMobile()) {
            $(this)
                .find(".dropdown-menu")
                .css("top", $(this).position().top);
        }
    });
    $("#madinah-carousel").carousel({ interval: false, autoplay: false });
    $("#carouselTrending").carousel({ interval: false, autoplay: false });

    $.ajax({
        type: "GET",
        url: DEPT_API,
        dataType: "json",
        success: function(data) {
            const {
                all_departments,
                trending_categories,
                trending_products
            } = data;
            var $carouselInner = $("#carousel-inner");
            var $carouselInnertrend = $("#carousel-inner-trending");
            var deptToAppend = "";
            if (isMobile()) {
                trending_categories.map((item, index) => {
                    var $item = jQuery("<div/>", {
                        class:
                            index == 0
                                ? "carousel-item col-sm-12  active"
                                : "carousel-item col-sm-12"
                    }).appendTo($carouselInner);

                    var anchor = jQuery("<a/>", {
                        href: item.link
                    }).appendTo($item);
                    var img = jQuery("<img/>", {
                        src: `${item.image}`,
                        height: "150px"
                    }).appendTo(anchor);
                    var div = jQuery("<div/>", {
                        class: "col-sm-12"
                    }).appendTo(anchor);
                    var span = jQuery("<span/>", {
                        html: `${item.category}`,
                        class: "top-trending-text text-center"
                    }).appendTo(div);
                    var li = jQuery("<li/>", {
                        "data-target": "#madinah-carousel",
                        "data-slide-to": index,
                        class: index == 0 ? "active" : ""
                    }).appendTo("#madinahcarouselindicator");
                    // slickLightboxcode()
                });
                slickLightboxcode();

                trending_products.map((item, index) => {
                    var $item = jQuery("<div/>", {
                        class:
                            index == 0
                                ? "carousel-item col-sm-12  active"
                                : "carousel-item col-sm-12"
                    }).appendTo($carouselInnertrend);
                    var lightbox = jQuery("<a/>", {
                        class: "light-box",
                        href: item.image,
                        "data-caption": ""
                    }).appendTo($item);
                    var img = jQuery("<img/>", {
                        src: `${item.main_image}`
                    }).appendTo(lightbox);
                    var div = jQuery("<div/>", {
                        html: `${item.site}`,
                        class: "top-trending-site text-center"
                    }).appendTo($item);
                    var nameLink = jQuery("<a/>", {
                        target: "_blank",
                        class: "tr-productName",
                        href: item.product_url
                    }).appendTo($item);
                    var div = jQuery("<h3/>", {
                        html: `${item.name}`,
                        class: "top-trending-text text-center"
                    }).appendTo(nameLink);
                    var priceLink = jQuery("<a/>", {
                        target: "_blank",
                        class: "tr-productPrice",
                        href: item.product_url
                    }).appendTo($item);
                    var pricediv = jQuery("<div/>", {
                        class: "prod-price-div"
                    }).appendTo(priceLink);
                    var li = jQuery("<li/>", {
                        "data-target": "#carouselTrending",
                        "data-slide-to": index,
                        class: index == 0 ? "active" : ""
                    }).appendTo("#toptrendingindicator");

                    if (item.is_price.includes("-")) {
                        let salepriceRange = item.is_price.split("-");

                        var saleprice = jQuery("<span />", {
                            text: `$${Math.round(
                                salepriceRange[0]
                            ).toLocaleString()} - $${Math.round(
                                salepriceRange[1]
                            ).toLocaleString()}`,
                            class: "prod-sale-price d-md-none"
                        }).appendTo(pricediv);
                    } else {
                        var saleprice = jQuery("<span />", {
                            text: `$${Math.round(
                                item.is_price
                            ).toLocaleString()}`,
                            class: "prod-sale-price d-md-none"
                        }).appendTo(pricediv);
                    }
                    if (item.was_price !== item.is_price) {
                        if (item.was_price.includes("-")) {
                            let salepriceRange = item.was_price.split("-");
                            var saleprice = jQuery("<span />", {
                                text: `$${Math.round(
                                    salepriceRange[0]
                                ).toLocaleString()} - $${Math.round(
                                    salepriceRange[1]
                                ).toLocaleString()}`,
                                class: "prod-was-price d-md-none"
                            }).appendTo(pricediv);
                        } else {
                            var saleprice = jQuery("<span />", {
                                text: `$${Math.round(
                                    item.was_price
                                ).toLocaleString()}`,
                                class: "prod-was-price d-md-none"
                            }).appendTo(pricediv);
                        }
                    }

                    var collapseBtnDiv = jQuery("<div/>", {
                        class: "collapse-btn"
                    }).appendTo($item);
                    var collapseBtn = jQuery("<a/>", {
                        class: "load-more-button collapsed",
                        "data-toggle": "collapse",
                        href: "#collapseExample" + index + "",
                        role: "button",
                        "aria-expanded": "false",
                        "aria-controls": "collapseExample"
                    }).appendTo(collapseBtnDiv);
                    var loadMore = jQuery("<span/>", {
                        class: "more",
                        html:
                            '<i class="fa fa-angle-down" aria-hidden="true"></i> More '
                    }).appendTo(collapseBtn);
                    var loadLess = jQuery("<span/>", {
                        class: "less",
                        html:
                            '<i class="fa fa-angle-up" aria-hidden="true"></i> Less '
                    }).appendTo(collapseBtn);
                    var collapseMainDiv = jQuery("<div/>", {
                        class: "collapse",
                        id: "collapseExample" + index + ""
                    }).appendTo($item);
                    var collapseInnerDiv = jQuery("<div/>", {
                        class: "class-body top-trending-text text-center",
                        html: md.render(item.description.join("\n"))
                    }).appendTo(collapseMainDiv);
                });

                // $('#carouselTrending').slick({
                //     infinite: true,
                //     slidesToShow: 3,
                //     slidesToScroll: 1,
                //     mobileFirst: true
                // })
                // $('#carouselTrending').slick({
                //     itemSelector: 'a',
                //     navigateByKeyboard: true,
                //     captionPosition: 'dynamic',
                //     layouts: {
                //         closeButton:
                //             '<button type="button" class="slick-lightbox-close"></button>'
                //     }
                // })

                $("#collapsible-dept").empty();
                var deptToAppend = "";
                for (var i = 0; i < all_departments.length; i++) {
                    if (all_departments[i].categories.length == 0) {
                        deptToAppend +=
                            '<li class="department"><a class="link collapsible" href="' +
                            all_departments[i].link +
                            '">' +
                            all_departments[i].department +
                            "</a></li>";
                    } else {
                        deptToAppend +=
                            '<li class="department"><a  class="collapsible" data-toggle="collapse" data-target="#' +
                            all_departments[i].department +
                            '"><span class="link">' +
                            all_departments[i].department +
                            '</span><span  class="side-nav-icon" id="navbarDropdown' +
                            i +
                            '"><i class="fas fa-angle-right arrow"></i></span></a>';
                        var catgToAppend =
                            '<ul class="collapse category-list" aria-labelledby="navbarDropdown" id="' +
                            all_departments[i].department +
                            '">';
                        for (
                            var j = 0;
                            j < all_departments[i].categories.length;
                            j++
                        ) {
                            catgToAppend +=
                                '<li><a class="link" href="' +
                                all_departments[i].categories[j].link +
                                '">' +
                                all_departments[i].categories[j].category +
                                "</a></li>";
                        }
                        catgToAppend += "</ul>";
                        deptToAppend += catgToAppend;
                        deptToAppend += "</li>";
                    }
                }
                $("#collapsible-dept").html(deptToAppend);
                var singleDeptMobile = "";
                for (var i = 0; i < all_departments.length; i++) {
                    if (all_departments.length != 0) {
                        singleDeptMobile =
                            '<div class="col-sm-4  -dept "><a  href="' +
                            all_departments[i].link +
                            '">' +
                            all_departments[i].department +
                            "</a></div>";
                    }
                    $("#mobileDepartments").append(singleDeptMobile);
                }
            }

            for (var i = 0; i < all_departments.length; i++) {
                if (all_departments[i].categories.length == 0) {
                    deptToAppend +=
                        '<li><a href="' +
                        all_departments[i].link +
                        '">' +
                        all_departments[i].department +
                        "</a></li>";
                } else {
                    let classActive =
                        all_departments[i].link === location.pathname
                            ? "active"
                            : "";
                    deptToAppend +=
                        '<li class="dropdown ' +
                        classActive +
                        '"><a  href="' +
                        all_departments[i].link +
                        '" id="navbarDropdown' +
                        i +
                        '" role="button"  aria-haspopup="true" aria-expanded="false">' +
                        all_departments[i].department +
                        "</a>";
                    var catgToAppend =
                        '<ul class="dropdown-menu" aria-labelledby="navbarDropdown">';
                    for (
                        var j = 0;
                        j < all_departments[i].categories.length;
                        j++
                    ) {
                        // if (all_departments[i].categories[j].sub_categories.length == 0) {
                        catgToAppend +=
                            '<li><a href="' +
                            all_departments[i].categories[j].link +
                            '">' +
                            all_departments[i].categories[j].category +
                            "</a></li>";
                        // }
                        // else {
                        //   catgToAppend += '<li class="dropdown-submenu">';
                        //   catgToAppend += '<a href="'+all_departments[i].categories[j].link+'">' + all_departments[i].categories[j].category + '<span class="mx-2"><i class="fas fa-angle-right"></i></span>';
                        //   var subcatToAppend = '<ul class="dropdown-menu">';
                        //   for (k = 0; k < all_departments[i].categories[j].sub_categories.length; k++) {
                        //     subcatToAppend += '<li><a href="' + all_departments[i].categories[j].sub_categories[k].link + '">' + all_departments[i].categories[j].sub_categories[k].sub_category + '</a></li>'
                        //   }

                        //   subcatToAppend += '</ul>';
                        //   catgToAppend += subcatToAppend;
                        //   catgToAppend += '</li>';
                        // }
                    }
                    catgToAppend += "</ul>";
                    deptToAppend += catgToAppend;
                    deptToAppend += "</li>";
                }
            }
            $("#departmentsNav").append(deptToAppend);
        },
        error: function(jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
        }
    });
});

export default function isMobile() {
    var isMobile = window.matchMedia("only screen and (max-width: 768px)");
    return isMobile.matches ? true : false;
}
