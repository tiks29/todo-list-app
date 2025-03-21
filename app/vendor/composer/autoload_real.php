<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitb295ddb055980e43e50f750e8dfeb5e7
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitb295ddb055980e43e50f750e8dfeb5e7', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitb295ddb055980e43e50f750e8dfeb5e7', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitb295ddb055980e43e50f750e8dfeb5e7::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
