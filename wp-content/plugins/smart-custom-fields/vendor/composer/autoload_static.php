<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6caf509ee70c5c9cdd5c56aba5d8af0f
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit6caf509ee70c5c9cdd5c56aba5d8af0f::$classMap;

        }, null, ClassLoader::class);
    }
}