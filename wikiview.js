// ==UserScript==
// @name            WikiView: AQW Link Preview
// @namespace       https://github.com/biglavis/
// @version         1.1.2
// @description     Adds image previews for links on the official AQW Wiki, AQW character pages, and AQW account management.
// @match           http://aqwwiki.wikidot.com/*
// @match           https://account.aq.com/CharPage?id=*
// @match           https://account.aq.com/AQW/Inventory
// @match           https://account.aq.com/AQW/BuyBack
// @match           https://account.aq.com/AQW/WheelProgress
// @match           https://account.aq.com/AQW/House
// @exclude         http://aqwwiki.wikidot.com/book-of-lore-badges
// @exclude         http://aqwwiki.wikidot.com/character-page-badges
// @require         https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js
// @icon            https://www.aq.com/favicon.ico
// @license         MIT
// ==/UserScript==

var mousePos = { x: -1, y: -1 };
    $(document).mousemove(function(event) {
        mousePos.x = event.clientX;
        mousePos.y = event.clientY;

        if (!mouseOn) removePreview();
    });

var mouseOn = false; // flag to prevent spam
var timeout = null;

$("#page-content a, .card.m-2.m-lg-3 a").on({
    mouseover: function() { hovered(this.href); },
    mouseout: function() { unhovered(); }
});

$("#inventoryRendered, #site-changes-list").on("mouseover", function() {
    $(this).find("a").on({
        mouseover: function() { hovered(this.href); },
        mouseout: function() { unhovered(); }
    });
});

$("#listinvFull, #wheel, table.table.table-sm.table-bordered").on("mouseover", function() {
    $(this).find("tbody td:first-child").on({
        mouseover: function() { hovered("http://aqwwiki.wikidot.com/" + this.textContent.split(/\sx\d+/)[0]); },
        mouseout: function() { unhovered(); }
    });
});

$("#listinvBuyBk").on("mouseover", function() {
    $(this).find("tbody td:nth-child(2)").on({
        mouseover: function() { hovered("http://aqwwiki.wikidot.com/" + this.textContent); },
        mouseout: function() { unhovered(); }
    });
});

function hovered(link) {
    if (!mouseOn) {
        mouseOn = true;
        // show preview if hovered for 100ms
        timeout = setTimeout(function() {
            removePreview(); // remove previous preview
            showPreview(link);
        }, 100);
    }
}

function unhovered() {
    clearTimeout(timeout);
    mouseOn = false;
}

function showPreview(link) {
    if (!link.startsWith("http://aqwwiki.wikidot.com/")) return;

    fetch("https://whoasked.freewebhostmost.com/wikimg.php?page=" + link)
        .then(function(response) {
            // convert page to text
            return response.text()
        })
        .then(function(html) {
            // parse text
            const doc = new DOMParser().parseFromString(html, "text/html");

            // get images
            const images = $(doc).find("body img");

            // return if no images found
            if (images.length == 0) return;

            const maxwidth = $(window).width() * 0.45;
            const maxheight = $(window).height() * 0.65;

            removePreview(); // remove previous preview
            $("body").append('<div id="preview" style="position:fixed; display:flex"></div>');

            // add images to new div
            images.each(function () {
                if (images.length == 1) {
                    $("#preview").append(`<img style="max-width:${maxwidth}px; max-height:${maxheight}px; height:auto; width:auto;" src="${this.src}">`);
                } else {
                    $("#preview").append(`<img style="height:${maxheight}px;" src="${this.src}">`);
                }
            });

            // wait for images to load
            waitForImg("#preview img:last", function() {

                // scale images down if div width > max width
                const scale = maxwidth / $("#preview").width();
                $("#preview img").each(function() {
                    this.style.height = this.offsetHeight * Math.min(1, scale) + "px";
                });

                // position div
                $("#preview").css("top", mousePos.y - (mousePos.y / $(window).height()) * $("#preview").height() + "px");
                if (mousePos.x < $(window).width() / 2) {
                    $("#preview").css("left", mousePos.x + Math.min(100, $(window).width() - $("#preview").width() - mousePos.x) + "px");
                } else {
                    $("#preview").css("right", $(window).width() - mousePos.x + Math.min(100, mousePos.x - $("#preview").width()) + "px");
                }
            });
        })
        .catch(function(err) {
            console.log("Failed to fetch page: ", err);
        });
}

function removePreview() {
    $("#preview").remove();
}

function waitForImg(selector, callback) {
    let wait = setInterval(function(){
        try {
            if( $(selector)[0].complete ) {
                callback();
                clearInterval(wait);
            }
        } catch {
            clearInterval(wait);
        }
    }, 25);
}
