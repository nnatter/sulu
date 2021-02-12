<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Interface to load content.
 */
class ContentTwigExtension extends AbstractExtension implements ContentTwigExtensionInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer,
        LoggerInterface $logger = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->logger = $logger ?: new NullLogger();
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_load', [$this, 'load']),
            new TwigFunction('sulu_content_load_parent', [$this, 'loadParent']),
        ];
    }

    public function load($uuid, array $properties = null)
    {
        if (!$uuid) {
            return;
        }

        try {
            $contentStructure = $this->contentMapper->load(
                $uuid,
                $this->requestAnalyzer->getWebspace()->getKey(),
                $this->requestAnalyzer->getCurrentLocalization()->getLocale()
            );

            if (null === $properties) {
                return $this->structureResolver->resolve($contentStructure);
            }

            $contentProperties = [];
            $extensionProperties = [];
            foreach ($properties as $sourceProperty => $targetProperty) {
                if (!\is_string($sourceProperty)) {
                    $sourceProperty = $targetProperty;
                }

                if (!\strpos($sourceProperty, '.')) {
                    $contentProperties[$sourceProperty] = $targetProperty;
                } else {
                    $extensionProperties[$sourceProperty] = $targetProperty;
                }
            }

            $resolvedStructure = $this->structureResolver->resolve(
                $contentStructure,
                !empty($extensionProperties),
                \array_keys($contentProperties)
            );

            foreach ($contentProperties as $sourceProperty => $targetProperty) {
                if ($sourceProperty !== $targetProperty) {
                    $resolvedStructure['content'][$targetProperty] = $resolvedStructure['content'][$sourceProperty];
                    $resolvedStructure['view'][$targetProperty] = $resolvedStructure['view'][$sourceProperty];

                    unset($resolvedStructure['content'][$sourceProperty]);
                    unset($resolvedStructure['view'][$sourceProperty]);
                }
            }

            foreach ($extensionProperties as $sourceProperty => $targetProperty) {
                [$extensionName, $propertyName] = \explode('.', $sourceProperty);
                $propertyValue = $resolvedStructure['extension'][$extensionName][$propertyName];

                $resolvedStructure['content'][$targetProperty] = $propertyValue;
                $resolvedStructure['view'][$targetProperty] = [];
            }
            unset($resolvedStructure['extension']);

            return $resolvedStructure;
        } catch (DocumentNotFoundException $e) {
            $this->logger->error((string) $e);

            return;
        }
    }

    public function loadParent($uuid, array $properties = null)
    {
        $session = $this->sessionManager->getSession();
        $contentsNode = $this->sessionManager->getContentNode($this->requestAnalyzer->getWebspace()->getKey());
        $node = $session->getNodeByIdentifier($uuid);

        if ($node->getDepth() <= $contentsNode->getDepth()) {
            throw new ParentNotFoundException($uuid);
        }

        return $this->load($node->getParent()->getIdentifier(), $properties);
    }
}
