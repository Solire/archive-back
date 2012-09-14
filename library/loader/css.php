<?php

namespace Slrfw\Library\Loader;

/** @todo faire la présentation du code */

class Css {

    private $libraries;

    public function __construct() {
        $this->libraries = array();
    }

    public function getLibraries() {
        return $this->libraries;
    }

    public function loadedLibraries() {
        return array_keys($this->libraries);
    }

    public function  __toString()
    {
        $css = "";
        foreach ($this->libraries as $lib)
            $css .= '<link rel="stylesheet" href="' . $lib["src"] . '" type="text/css" media="' . $lib["media"] . '" title="" charset="utf-8" />' . "\n\t";
        return $css;
    }



    public function addLibrary($path, $media = "screen")
    {
        if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
            $this->libraries[] = array(
                "src" => (substr($path, 0, 7) == 'http://' || substr($path, 0, 8) == 'https://' ? '' : 'css/') . $path,
                "media" => $media,
            );
    }

}
