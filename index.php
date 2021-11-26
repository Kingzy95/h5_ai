<?php

require_once './.my_h5ai/vendor/autoload.php';
require_once './.my_h5ai/H5AITwigExtension.php';

/**
 * Retourne la liste des répertoires et sous-répertoires d'un dossier donné
 * @param string $base_path
 * @param string $path
 * @return array
 */
function h5ai_get_directories(string $base_path, string $path = ''): array
{
    $result = [];
    if (($handle = opendir($base_path . $path)) !== false) {
        while (($entry = readdir($handle)) !== false) {
            if ($entry[0] === '.')
                continue;

            if (is_dir($base_path . $path . '/' . $entry)
                && $entry !== '.' && $entry !== '..') {
                $result[] = [
                    'name' => $entry,
                    'full_path' => $path . '/' . $entry . '/',
                    'dirs' => h5ai_get_directories($base_path, $path . '/' . $entry)];
            }
        }
        closedir($handle);
    }
    uasort($result, function($a, $b) {
        return $a['name'] <=> $b['name'];
    });
    return $result;
}

/**
 * Retourne des informations sur chaque fichier/répertoire d'un dossier
 * @param string $path
 * @return array
 */
function h5ai_get_dir_contents(string $path): array
{
    $result = [];
    if (($handle = opendir($path)) !== false) {
        while(($entry = readdir($handle)) !== false) {
            $full_path = $path . '/' . $entry;
            if (is_file($full_path)) {
                if ($entry[0] === '.')
                    continue;
                $result[$entry]['name'] = $entry;
                $result[$entry]['type'] = 'file';
                $result[$entry]['mtime'] = filemtime($full_path);
                $result[$entry]['size_unformatted'] = filesize($full_path);
                $result[$entry]['size'] = h5ai_format_filesize($result[$entry]['size_unformatted']);
            }
            elseif (is_dir($full_path) && $entry !== '.') {
                if ($entry[0] === '.' && $entry != '..')
                    continue;
                $result[$entry]['name'] = $entry;
                $result[$entry]['type'] = 'directory';
                $result[$entry]['mtime'] = filemtime($full_path);
                if ($entry !== '..') {
                    $result[$entry]['size_unformatted'] = h5ai_get_dir_size($full_path);
                    $result[$entry]['size'] = h5ai_format_filesize($result[$entry]['size_unformatted']);
                }
            }
        }
        closedir($handle);
    }
    usort($result, function($a, $b) {
        return $a['type'] <=> $b['type'];// ? ($b['type'] == 'directory') : ($a['name'] > $b['name']);
        //return $a['type'] !== $b['type'] ? ($b['type'] == 'directory') : ($a['name'] > $b['name']);
    });
    return $result;
}


/**
 * Retourne la taille du contenu d'un répertoire
 * @param string $directory
 * @return array
 */
function h5ai_get_dir_size(string $directory): array
{
    $size = 0;
    if (($handle = opendir($directory)) !== false) {
        while (($entry = readdir($handle)) !== false) {
            $path = $directory . '/' . $entry;
            if (is_dir($path) && $entry !== '.' && $entry !== '..')
                $size += h5ai_get_dir_size($path);
            elseif (is_file($path))
                $size += filesize($path);
        }
    }
    return $size;
}

/**
 * Convertit une taille en octets en sa représentation dans une unité plus proche
 * @param int $size
 * @return string
 */
function h5ai_format_filesize(int $size): string
{
    $units = array( ' bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power)). $units[$power];
}

// Obtenez le répertoire absolu qui contient le script en cours.
$script_dir = dirname(__FILE__);

// Obtenir son cheminement relatif vers le DOCUMENT_ROOT.
$relative_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', $script_dir);

// Obtenez l'arborescence des répertoires à partir du répertoire du script.
$dir_tree = h5ai_get_directories($_SERVER['DOCUMENT_ROOT'], $relative_dir);

// Obtenez le contenu du répertoire du chemin passé en URL.
$dir_contents = h5ai_get_dir_contents($script_dir . $_SERVER['PATH_INFO']);

// Charger l'environnement Twig et instancier l'extension
$loader = new Twig\Loader\FilesystemLoader('./.my_h5ai/templates/');
$twig = new Twig\Environment($loader, ['cache' => false ]);
$twig->addExtension(new H5AITwigExtension());

// Variables passées au modèle :
// request_path -- Le chemin passé à l'URL du script (ex. index.php/mon/répertoire/ => /mon/répertoire/)
// directory_tree -- L'arborescence des répertoires affichée à gauche
// current_dir -- Le contenu du répertoire courant
// path_breadcrumb -- Un tableau de chaque répertoire parent jusqu'au répertoire actuel
// relative_script_dir -- Le chemin relatif entre DOCUMENT_ROOT et le script. Utilisé pour la génération d'URL.
try {
    echo $twig->render('index.html.twig', [
        'request_path' => $_SERVER['PATH_INFO'],
        'directory_tree' => $dir_tree,
        'current_dir' => $dir_contents,
        'path_breadcrumb' => explode('/', trim($_SERVER['PATH_INFO'], '/')),
        'relative_script_dir' => $relative_dir
    ]);
} catch (\Twig\Error\LoaderError | \Twig\Error\RuntimeError | \Twig\Error\SyntaxError $e) {
}