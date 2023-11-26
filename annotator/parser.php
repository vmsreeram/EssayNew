<?php
/**
 * @author Asha Jose, Parvathy S
 * This page contains functions for parsing
 * The data from serialiser of fabricjs is read and processed.
 * The processed data is then stored in utiliszable manner for fpdf
 */

define("RATIO", 0.238); //Ratio to convert FabricJS objects to FPDF objects

/**
 * for finding corresponding size of fpdf object given a fabricjs object
 * 
 * @param float $fabricjsUnit variable corresponding to FabricJS distance unit(px)
 * @return $fpdfUnit corresponding distance unit value in FPDF
 */
function normalize($fabricjsUnit)
{
    $fpdfUnit= RATIO * $fabricjsUnit;
    return $fpdfUnit;
}

/**
 * Parser for free hand drawings
 * Given an array containing the data related to FabricJS path object,
 * deserialize it, taking the relevant data to convert the path to an FPDF line object.
 *
 * @param object $arrPath the array containing data related to a path object.
 * @return $list deserialized data for the path in FPDF line format
 */
function parser_path($arrPath) 
{
    // stored as a set of points (x and y coordinates)
    $list = array();
    $len = count($arrPath["path"]);
    for($i = 0; $i < $len-1 ; $i++)
    {
        if($i == 0 || $i == $len-2)  //First and Last array elements are dummy values 
            continue;               

        $temp = array();
        array_push($temp,normalize($arrPath["path"][$i][1]));  //x1
        array_push($temp,normalize($arrPath["path"][$i][2]));  //y1
        array_push($list,$temp);
        $temp = array();
        array_push($temp,normalize($arrPath["path"][$i][3]));  //x2
        array_push($temp,normalize($arrPath["path"][$i][4]));  //y2
        array_push($list,$temp);
    }
   array_push($list,$arrPath["stroke"]);                       // stroke color
   return $list;
}

/**
 * Parser for text
 * Given an array containing the data related to FabricJS text object,
 * deserialize it, taking the relevant data to convert the text to an FPDF text object.
 *
 * @param object $arrText the array containing data related to a text object.
 * @return $list deserialized data in FPDF text format
 */
function parser_text($arrText)
{
    $list = array();
    // left and top refers to x and y coordinates of top left corner
    array_push($list,normalize($arrText["left"]),normalize($arrText["top"]),
                normalize($arrText["width"]),normalize($arrText["height"]));
    // text refers to the content and fill is the color of the text
    array_push($list,$arrText["text"],$arrText["fill"]);
    array_push($list,$arrText["fontSize"]);
    return $list;
}

/**
 * Parser for rectangle
 * Given an array containing the data related to FabricJS Rect object,
 * deserialize it, taking the relevant data to convert the Rect to an FPDF Rect object.
 * scaling factor is also taken into consideration while transforming.
 *
 * @param object $arrRect the array containing data related to a Rect object.
 * @return $list deserialized data in FPDF Rect format
 */
function parser_rectangle($arrRect)
{
    $list = array();
    // scaleX and scaleY is 1 if the rectangle is not transformed
    // during transformation, width and height remains same but the scaleX and scaleY change
    $width=(normalize($arrRect["width"]))*($arrRect["scaleX"]); 
    $height=(normalize($arrRect["height"]))*($arrRect["scaleY"]);
    array_push($list,normalize($arrRect["left"]),normalize($arrRect["top"]),$width,$height);
    array_push($list,$arrRect["fill"]);
    return $list;

}

/**
 * Utility function for converting color format from fabricJS to rgb values
 * Given a string containing the color data in fabricJS format,
 * return the corresponding color values in [r,g,b]
 * 
 * @param string $colorString the string containing color data
 * @return array $rgb colors in [r,g,b] format
 */
function process_color($colorString) {
    if ($colorString == "null")    
        $rgb = [0, 0, 0];
    if ($colorString == "red" || $colorString == "rgba(251, 17, 17, 0.3)" || $colorString == "rgb(251, 17, 17)")                 
        $rgb = [251, 17, 17];              // converting string to rgb
    else if ($colorString == "green" || $colorString == "rgba(13, 93, 13, 0.3)" || $colorString == "rgb(13, 93, 13)")
        $rgb = [13, 93, 13];
    else if($colorString == "blue" || $colorString == "rgba(2, 2, 182, 0.3)" || $colorString == "rgb(2, 2, 182)")
        $rgb = [2, 2, 182];
    else if($colorString == "black" || $colorString == "rgba(0, 0, 0, 0.3)" || $colorString == "rgb(0, 0, 0)")
        $rgb = [0, 0, 0];
        else if($colorString == "yellow" || $colorString == "rgba(255, 255, 0, 0.3)" || $colorString == "rgb(255, 255, 0)")
        $rgb = [255, 255, 0];
    else {
        $rgb = array();
        list($r, $g, $b) = sscanf($colorString, "#%02x%02x%02x"); //hexadecimal format
        $rgb = [$r, $g, $b];
    }
    return $rgb;
}
?>
