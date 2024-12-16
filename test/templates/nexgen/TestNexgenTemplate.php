<?php
/**
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once './core/template.php';
require_once './vendor/smarty/smarty/libs/Smarty.class.php';

use PHPUnit\Framework\TestCase;

class TestNexgenTemplate extends TestCase
{

    private static $smarty;
    private static $templates;

    public static function setUpBeforeClass(): void
    {
        self::$templates = glob('./templates/nexgen/*.tpl', GLOB_BRACE);

        self::$smarty = new Smarty\Smarty;
        self::$smarty->force_compile = true;
        self::$smarty->addPluginsDir(['./lib/smarty/custom', './vendor/smarty/smarty/libs/plugins']);
        self::$smarty->setTemplateDir('./templates/nexgen');
        self::$smarty->setCompileDir(sys_get_temp_dir());
    }

    function printData($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    function testNexgenTemplates(): void
    {
        self::$smarty->registerPlugin("modifier", "min", "min");
        self::$smarty->registerPlugin("modifier", "max", "max");
        self::$smarty->registerPlugin("modifier", "preg_split", "preg_split");

        foreach(self::$templates as $template) {
            $basename = basename($template);
            $foo = self::$smarty->fetch($basename);

            $this->assertNotNull($foo);
        }
    }
}

?>