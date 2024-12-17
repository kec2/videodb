<?php
/**
 * @package Test
 */

require_once './vendor/smarty/smarty/libs/Smarty.class.php';

use PHPUnit\Framework\TestCase;

class TestModernTemplate extends TestCase
{

    private static $smarty;
    private static $templates;

    public static function setUpBeforeClass(): void
    {
        self::$templates = glob('./templates/modern/*.tpl', GLOB_BRACE);

        self::$smarty = new Smarty\Smarty;
        self::$smarty->force_compile = true;
        self::$smarty->addPluginsDir(['./lib/smarty/custom', './vendor/smarty/smarty/libs/plugins']);
        self::$smarty->setTemplateDir('./templates/modern');
        self::$smarty->setCompileDir(sys_get_temp_dir());
    }

    function printData($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    function testModernTemplates(): void
    {
        foreach(self::$templates as $template) {
            $basename = basename($template);
            $foo = self::$smarty->fetch($basename);

            $this->assertNotNull($foo);
        }
    }
}

?>