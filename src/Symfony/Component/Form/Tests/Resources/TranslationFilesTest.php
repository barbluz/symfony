<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Util\XliffUtils;

class TranslationFilesTest extends TestCase
{
    /**
     * @dataProvider provideTranslationFiles
     */
    public function testTranslationFileIsValid($filePath)
    {
        $document = new \DOMDocument();
        $document->loadXML(file_get_contents($filePath));

        $errors = XliffUtils::validateSchema($document);

        $this->assertCount(0, $errors, sprintf('"%s" is invalid:%s', $filePath, \PHP_EOL.implode(\PHP_EOL, array_column($errors, 'message'))));
    }

    /**
     * @dataProvider provideTranslationFiles
     *
     * @group Legacy
     */
    public function testTranslationFileIsValidWithoutEntityLoader($filePath)
    {
        $document = new \DOMDocument();
        $document->loadXML(file_get_contents($filePath));
        if (\LIBXML_VERSION < 20900) {
            libxml_disable_entity_loader(true);
        }

        $errors = XliffUtils::validateSchema($document);

        $this->assertCount(0, $errors, sprintf('"%s" is invalid:%s', $filePath, \PHP_EOL.implode(\PHP_EOL, array_column($errors, 'message'))));
    }

    public static function provideTranslationFiles()
    {
        return array_map(
            function ($filePath) { return (array) $filePath; },
            glob(\dirname(__DIR__, 2).'/Resources/translations/*.xlf')
        );
    }

    public function testNorwegianAlias()
    {
        $this->assertFileEquals(
            \dirname(__DIR__, 2).'/Resources/translations/validators.nb.xlf',
            \dirname(__DIR__, 2).'/Resources/translations/validators.no.xlf',
            'The NO locale should be an alias for the NB variant of the Norwegian language.'
        );
    }
}
