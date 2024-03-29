<?php
header("Access-Control-Allow-Origin: *");

$EXCLUDE = array(   "acsmall.png", "aclarge.png",
                    "membersmall.png", "memberlarge.png",
                    "legendsmall.png", "legendlarge.png",
                    "raresmall.png", "rarelarge.png",
                    "pseudosmall.png", "pseudolarge.png",
                    "seasonalsmall.png", "seasonallarge.png",
                    "specialsmall.png", "speciallarge.png",
                    "upholdersmall.png", "upholderlarge.png",
                    "betasmall.png", "betalarge.png",
                    "ptrsmall.png", "ptrlarge.png"              );

function wikimg($page) {
    global $EXCLUDE;

    // get HTML
    $curl = curl_init($page);

    curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.3');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $html = curl_exec($curl);
    
    // create DOMDocument from HTML
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $finder = new DomXPath($doc);

    // get page type
    $type = $finder->query("//div[@id='breadcrumbs']/a[last()]")->item(0)->nodeValue;

    // get images
    switch ($type) {
        case "AQWorlds Wiki":
            if ($finder->query("(//div[@id='page-content']/*[1])[name()='p']")->length) {
                $links = $finder->query("//div[@id='page-content']//a");
                foreach($links as $link) {
                    $images = wikimg("http://aqwwiki.wikidot.com" . $link->getAttribute("href"));
                    if ($images) return $images;
                }
            }
            return;

        case "Events":
        case "Factions":
        case "Game Menu":
        case "Quests":
        case "Shops":
        case "Hair Shops":
        case "Merge Shops":
        case "Enhancements":
            return;

        case "Book of Lore Badges":
        case "Character Page Badges":
            $image = $finder->query("//div[@id='page-content']//img[last()]")->item(0);
            return array($doc->saveHTML($image));

        case "Classes":
        case "Armors":
            $image0 = $finder->query("(//div[@id='wiki-tab-0-0'])[last()]//img[1]")->item(0);
            $image1 = $finder->query("(//div[@id='wiki-tab-0-1'])[last()]//img[1]")->item(0);

            if ($image0 && $image1) {
                $image0->setAttribute("height", "65%");
                $image1->setAttribute("height", "65%");

                return array($doc->saveHTML($image0), $doc->saveHTML($image1));
            }

        default:
            $image = $finder->query("//div[@id='page-content']/img[last()]")->item(0);
            if ($image && !in_array($image->getAttribute("alt"), $EXCLUDE)) {
                return array($doc->saveHTML($image));
            }

            $images = $finder->query("//div[@id='wiki-tab-0-0']//img");
            foreach ($images as $image)
                if ($image && !in_array($image->getAttribute("alt"), $EXCLUDE)) {
                    return array($doc->saveHTML($image));
                }

            $images = $finder->query("//div[@id='page-content']/div[@class='collapsible-block']//img");
            foreach ($images as $image)
                if ($image && !in_array($image->getAttribute("alt"), $EXCLUDE)) {
                    return array($doc->saveHTML($image));
                }
    }
}

$page = $_GET["page"];

if ($page && (  str_starts_with($page, "http://aqwwiki.wikidot.com/") || 
                str_starts_with($page, "aqwwiki.wikidot.com/")              )) {

    $images = wikimg("http://aqwwiki.wikidot.com/" . rawurlencode(explode(".com/", $page)[1]));
    
    if (!$images) echo "Error! Image not found.";
    else {
        foreach($images as $image) {
            echo $image;
        }
    }
}
?>