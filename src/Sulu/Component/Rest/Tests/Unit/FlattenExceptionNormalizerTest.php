<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Filter;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Sulu\Component\Rest\FlattenExceptionNormalizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlattenExceptionNormalizerTest extends TestCase
{
    public function testNormalizeGeneralExceptionDebugTrue()
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new \Exception('An unexpected error happened', 12345);
        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertSame(12345, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertArrayNotHasKey('detail', $result);
        $this->assertArrayHasKey('errors', $result);

        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Exception: An unexpected error happened in', $result['errors'][0]);
    }

    public function testNormalizeGeneralExceptionDebugFalse()
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new \Exception('An unexpected error happened', 12345);
        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(12345, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertArrayNotHasKey('detail', $result);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeTranslationErrorMessageExceptionDebugTrue()
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Key already exists', 56789) extends \Exception implements TranslationErrorMessageExceptionInterface {
            public function getMessageTranslationKey(): string
            {
                return 'error_message_translation_key';
            }

            public function getMessageTranslationParameters(): array
            {
                return [
                    'parameter1' => 'value1',
                    'parameter2' => 'value2',
                ];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $translator->trans(
            'error_message_translation_key',
            [
                'parameter1' => 'value1',
                'parameter2' => 'value2',
            ],
            'admin'
        )->willReturn('Translated Error Message Example');

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertSame(56789, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertSame('Translated Error Message Example', $result['detail']);
        $this->assertArrayHasKey('errors', $result);

        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Key already exists in', $result['errors'][0]);
    }

    public function testNormalizeTranslationErrorMessageExceptionDebugFalse()
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Key already exists', 56789) extends \Exception implements TranslationErrorMessageExceptionInterface {
            public function getMessageTranslationKey(): string
            {
                return 'error_message_translation_key';
            }

            public function getMessageTranslationParameters(): array
            {
                return [
                    'parameter1' => 'value1',
                    'parameter2' => 'value2',
                ];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $translator->trans(
            'error_message_translation_key',
            [
                'parameter1' => 'value1',
                'parameter2' => 'value2',
            ],
            'admin'
        )->willReturn('Translated Error Message Example');

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(56789, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertSame('Translated Error Message Example', $result['detail']);
        $this->assertArrayNotHasKey('errors', $result);
    }
}
