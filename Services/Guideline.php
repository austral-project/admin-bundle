<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Services;

use Austral\ToolsBundle\AustralTools;

/**
 * Austral Guideline.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class Guideline
{

  public function __construct()
  {

  }

  /**
   * @return array[]
   * @throws \Exception
   */
  public function retreiveFonts(): array
  {
    $fontFilePath = AustralTools::join(
      dirname(__FILE__),
      "../../design-bundle/Resources/assets/styles/base/font.scss",
    );
    if(!file_exists($fontFilePath))
    {
      throw new \Exception("The file {$fontFilePath} not exist !!!");
    }
    $contentFile = file_get_contents($fontFilePath);
    $contentFile = str_replace("\n",null, $contentFile);
    $contentFile = str_replace("}","}\n", $contentFile);
    $contentFile = str_replace("**/","**/\n", $contentFile);
    $contentFile = str_replace("/** GUIDELINE_START","\n\n/** GUIDELINE_START", $contentFile);
    $contentFile = str_replace("/** FONT_DEFINED_START","\n\n/** FONT_DEFINED_START", $contentFile);
    $contentFile = str_replace("/** FONT_DEFINED_STOP **/","\n/** FONT_DEFINED_STOP **/", $contentFile);
    preg_match_all("/(\/\*\* GUIDELINE_START -> .*\*\*\/\n(.*\n){0,}?\/\*\* GUIDELINE_END -> .*\*\*\/)/", $contentFile, $matches);

    $fontsList = array();
    preg_match("/(\/\*\* FONT_DEFINED_START \*\*\/\n(.*\n){0,}?\/\*\* FONT_DEFINED_STOP \*\*\/)/", $contentFile, $matchesFonts);
    preg_match_all("/(.*?):(.*?;)/", $matchesFonts[2], $matchesFontsList, PREG_SET_ORDER);

    foreach ($matchesFontsList as $matchesFontList)
    {
      $fonts = str_replace(";", "",  trim($matchesFontList[2]));
      $fonts = str_replace("'", "",  $fonts);
      $fonts = explode(",", $fonts);
      $fontsList[trim($matchesFontList[1])] = $fonts[0];
    }

    $weightByValue = array(
      400 =>  "regular",
      500 =>  "medium",
      600 =>  "semibold",
      700 =>  "bold",
    );

    $fontsAustral = array();
    $fontsType = array();
    $extendElements = array();
    foreach($matches[0] as $scssFont)
    {
      preg_match("/\/\*\* GUIDELINE_START -> (.*) \*\*\//", $scssFont, $matchesType);
      preg_match_all("/.(.*){(.*)}/", $scssFont, $matchesContents, PREG_SET_ORDER);

      $isAustralFonts = $matchesType[1] === "Austral Fonts";

      foreach($matchesContents as $matchesContent)
      {
        $fontName = trim($matchesContent[1]);
        $isAustralFonts ? $fontsAustral[$fontName] = array() : $fontsType[$fontName] = array();
        preg_match_all("/(.*?.*?);|(@extend.*?);/", $matchesContent[2], $matchesValues, PREG_SET_ORDER);
        foreach($matchesValues as $values)
        {
          if(strpos($values[0], "@extend") !== false)
          {
            preg_match_all("/@extend .(.*)/", $values[1], $matchesValue,PREG_SET_ORDER);
            $extendElements[$isAustralFonts ? "austral" : "fonts"][trim($matchesContent[1])] = $matchesValue[0][1];
          }
          else
          {
            preg_match_all("/(.*?):(.*?);/", $values[0], $matchesValue, PREG_SET_ORDER);
            foreach($matchesValue as $valueBrut)
            {
              $key = trim($valueBrut[1]);
              $value = trim($valueBrut[2]);
              if(strpos($value, "rem"))
              {
                $value = str_replace(array("rem"), null, $value);
                $value *= 10;
              }
              if(strpos($value, "em"))
              {
                $value = str_replace(array("em"), null, $value);
                $value *= 1000;
              }
              if($key == 'font-weight')
              {
                $value = $weightByValue[$value];
              }
              if($key == 'font-family')
              {
                $value = $fontsList[$value];
              }

              if($isAustralFonts)
              {
                $fontsAustral[$fontName][$key] = $value;
              }
              else
              {
                $fontsType[$fontName][$key] = $value;
              }
            }
          }
        }
      }
    }
    foreach($extendElements as $type => $values)
    {
      foreach($values as $element => $source)
      {
        if($type == "austral")
        {
          $fontsAustral[$element] = array_merge($fontsAustral[$source], $fontsAustral[$element]);
        }
        else
        {
          $fontsType[$element] = array_merge($fontsType[$source], $fontsType[$element]);
        }
      }
    }
    return array(
      "fontsAustral"    =>  $fontsAustral,
      "fontsType"       =>  $fontsType
    );
  }



  /**
   * @return array[]
   * @throws \Exception
   */
  public function retreiveColors(): array
  {
    $fontFilePath = AustralTools::join(
      dirname(__FILE__),
      "../../design-bundle/Resources/assets/styles/base/_root.scss",
    );
    if(!file_exists($fontFilePath))
    {
      throw new \Exception("The file {$fontFilePath} not exist !!!");
    }
    $contentFile = file_get_contents($fontFilePath);
    preg_match("/:root {(.*\n){0,}?}/", $contentFile, $matchesColors);
    preg_match_all("/--color-(.*?):(.*?;)/", $matchesColors[0], $matchesColorsList, PREG_SET_ORDER);

    $colors = array();
    foreach($matchesColorsList as $values)
    {
      $colorName = trim($values[1]);
      $keyColor = explode("-", $colorName);

      $fontColor = "color-white";

      if($keyColor[0] == "white")
      {
        $fontColor = "color-black";
      }
      elseif($keyColor[0] !== "black" && $keyColor[0] !== "google" && $keyColor[0] !== "facebook")
      {
        $value = AustralTools::getValueByKey($keyColor, "1", 100);
        if($value <= 50 && $keyColor[0] === "yellow")
        {
          $fontColor = "color-$keyColor[0]-100";
        }
        elseif($value <= 30)
        {
          $fontColor = "color-$keyColor[0]-100";
        }
      }
      elseif($colorName == "facebook-light-grey")
      {
        $fontColor = "color-facebook-black";
      }

      if(array_key_exists(1, $keyColor) && $keyColor[1] == "force")
      {
        $fontColor .= "-force";
      }

      $colors[$keyColor[0]][$colorName] = array(
        "color"         => str_replace(";", "", $values[2]),
        "font-color"    => $fontColor
      );
    }
    return array(
      "colors"    =>  $colors,
    );

  }


}