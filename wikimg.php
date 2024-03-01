<?php
header("Access-Control-Allow-Origin: *");

function wikimg($page) {
    // get HTML
    $curl = curl_init($page);

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
            $links = $finder->query("//div[@id='page-content']/a");
            foreach($links as $link) {
                $images = wikimg("http://aqwwiki.wikidot.com" . $link->getAttribute("href"));
                if ($images) 
                    return $images;
            }
            return;

        case "Events":
        case "Factions":
        case "Quests":
        case "Shops":
        case "Hair Shops":
        case "Merge Shops":
        case "Enhancements":
        case "Misc. Items":
        case "Use Items":
        case "Necklaces":
            return;

        case "Classes":
        case "Armors":
            if ($finder->query("//div[@class='yui-content']")->length > 0) {
                $image0 = $finder->query("//div[@id='wiki-tab-0-0']//img[parent::div]")->item(0);
                $image1 = $finder->query("//div[@id='wiki-tab-0-1']//img[parent::div]")->item(0);
    
                $image0->setAttribute("height", "65%");
                $image1->setAttribute("height", "65%");

                return array($doc->saveHTML($image0), $doc->saveHTML($image1));
            }

        default:
            $images = $finder->query("(//div[@id='wiki-tab-0-0'])[last()]//img[parent::div]")->item(0);
            if ($images)
                return array($doc->saveHTML($images));

            $images = $finder->query("//div[@id='page-content']/img[last()]")->item(0);
            if ($images)
                return array($doc->saveHTML($images));

            return;
    }
}

$page = $_GET["page"];

if ($page && (  str_starts_with($page, "http://aqwwiki.wikidot.com/") || 
                str_starts_with($page, "aqwwiki.wikidot.com/")              )) {

    $images = wikimg("http://aqwwiki.wikidot.com/" . rawurlencode(explode(".com/", $page)[1]));
    
    if ($images)
        foreach($images as $image) 
            echo $image;
    else 
        echo "Error! Image not found.";
}
?>