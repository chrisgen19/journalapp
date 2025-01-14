<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit90df5907b5effa208d26aaa05c75c558
{
    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Parsedown' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit90df5907b5effa208d26aaa05c75c558::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit90df5907b5effa208d26aaa05c75c558::$classMap;

        }, null, ClassLoader::class);
    }
}