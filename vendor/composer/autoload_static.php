<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7d423fddbd97ad0f0940a97ec9f7d38e
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib\\' => 10,
        ),
        'S' => 
        array (
            'SeanKndy\\UbntStats\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'SeanKndy\\UbntStats\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7d423fddbd97ad0f0940a97ec9f7d38e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7d423fddbd97ad0f0940a97ec9f7d38e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}