<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class H5AITwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('file_icon', [$this, 'getFileIcon'])
        ];
    }

    public function getFileIcon(string $path) {
        $file_ext = pathinfo($path, PATHINFO_EXTENSION);

        switch($file_ext) {
            case 'php':
                return 'blue php';
            case 'js':
                return 'yellow js';
            case 'html':
            case 'htm':
                return 'orange html5';
            case 'json':
            case 'xml':
                return 'blue code';
            case 'cpp':
                return 'code file outline';
            case 'css':
                return 'blue css3';
            case 'less':
            case 'sass':
                return 'sass';
            case 'vue':
                return 'green vuejs';
            case 'exe':
                return 'blue windows';
            case 'elf':
                return 'linux';
            case 'docx':
                return 'blue file word';
            case 'xlsx':
                return 'green file excel';
            case 'pptx':
                return 'red file powerpoint';
            case 'ogg':
            case 'wmv':
            case 'wav':
            case 'mp3':
                return 'pink music';
            case 'zip':
            case 'tar':
                return 'brown archive file';
            case 'pdf':
                return 'red pdf file';
            case 'mp4':
            case 'webm':
                return 'purple video';
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'bmp':
            case 'svg':
            case 'webp':
                return 'image outline';
            case 'txt':
                return 'file alternate outline';
            default:
                return 'file outline';
        }
    }
}
