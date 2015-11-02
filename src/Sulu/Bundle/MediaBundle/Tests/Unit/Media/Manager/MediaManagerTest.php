<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\ORM\EntityManager;
use FFMpeg\FFProbe;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\StorageManager\StorageManagerInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class MediaManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var ObjectProphecy
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy
     */
    private $collectionRepository;

    /**
     * @var ObjectProphecy
     */
    private $userRepository;

    /**
     * @var ObjectProphecy
     */
    private $em;

    /**
     * @var ObjectProphecy
     */
    private $storageManager;

    /**
     * @var ObjectProphecy
     */
    private $validator;

    /**
     * @var ObjectProphecy
     */
    private $formatManager;

    /**
     * @var ObjectProphecy
     */
    private $tagManager;

    /**
     * @var ObjectProphecy
     */
    private $typeManager;

    /**
     * @var PathCleanupInterface
     */
    private $pathCleaner;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FFProbe
     */
    private $ffprobe;

    public function setUp()
    {
        parent::setUp();

        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->collectionRepository = $this->prophesize(CollectionRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->em = $this->prophesize(EntityManager::class);
        $this->storageManager = $this->prophesize(StorageManagerInterface::class);
        $this->validator = $this->prophesize(FileValidatorInterface::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);
        $this->typeManager = $this->prophesize(TypeManagerInterface::class);
        $this->pathCleaner = $this->prophesize(PathCleanupInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->ffprobe = $this->prophesize(FFProbe::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->mediaManager = new MediaManager(
            $this->mediaRepository->reveal(),
            $this->collectionRepository->reveal(),
            $this->userRepository->reveal(),
            $this->em->reveal(),
            $this->storageManager->reveal(),
            $this->validator->reveal(),
            $this->formatManager->reveal(),
            $this->tagManager->reveal(),
            $this->typeManager->reveal(),
            $this->pathCleaner->reveal(),
            $this->tokenStorage->reveal(),
            $this->securityChecker->reveal(),
            $this->ffprobe->reveal(),
            $this->logger->reveal(),
            [
                'view' => 64,
            ],
            '/',
            0
        );
    }

    /**
     * @dataProvider provideGetByIds
     */
    public function testGetByIds($ids, $media, $result)
    {
        $this->mediaRepository->findMedia(Argument::any())->willReturn($media);
        $this->formatManager->getFormats(Argument::cetera())->willReturn(null);
        $medias = $this->mediaManager->getByIds($ids, 'en');

        for ($i = 0; $i < count($medias); ++$i) {
            $this->assertEquals($result[$i]->getId(), $medias[$i]->getId());
        }
    }

    public function testGetWithoutToken()
    {
        $this->tokenStorage->getToken()->willReturn(null);
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get(1);
    }

    public function testDeleteWithSecurity()
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(2);
        $media = $this->prophesize(Media::class);
        $media->getCollection()->willReturn($collection);
        $media->getFiles()->willReturn([]);

        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->securityChecker->checkPermission(
            new SecurityCondition('sulu.media.collections', null, Collection::class, 2),
            'delete'
        )->shouldBeCalled();

        $this->mediaManager->delete(1, true);
    }

    public function testDelete()
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(2);

        $file = $this->prophesize(File::class);
        $fileVersion = $this->prophesize(FileVersion::class);
        $file->getFileVersions()->willReturn([$fileVersion->reveal()]);
        $fileVersion->getId()->willReturn(1);
        $fileVersion->getName()->willReturn('test');
        $fileVersion->getStorageOptions()->willReturn(json_encode(['segment' => '01', 'fileName' => 'test.jpg']));

        $media = $this->prophesize(Media::class);
        $media->getCollection()->willReturn($collection);
        $media->getFiles()->willReturn([$file->reveal()]);
        $media->getId()->willReturn(1);

        $this->formatManager->purge(
            1,
            'test',
            json_encode(['segment' => '01', 'fileName' => 'test.jpg'])
        )->shouldBeCalled();

        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->securityChecker->checkPermission(
            new SecurityCondition('sulu.media.collections', null, Collection::class, 2),
            'delete'
        )->shouldBeCalled();

        $this->storage->remove(json_encode(['segment' => '01', 'fileName' => 'test.jpg']))->shouldBeCalled();

        $this->mediaManager->delete(1, true);
    }

    /**
     * @dataProvider provideSpecialCharacterFileName
     */
    public function testSpecialCharacterFileName($fileName, $cleanUpArgument)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $this->prophesize(UploadedFile::class)->willBeConstructedWith(['', 1, null, null, 1, true]);
        $uploadedFile->getClientOriginalName()->willReturn($fileName);
        $uploadedFile->getPathname()->willReturn('');
        $uploadedFile->getSize()->willReturn('123');
        $uploadedFile->getMimeType()->willReturn('img');

        $user = $this->prophesize(User::class)->willImplement(UserInterface::class);
        $this->userRepository->findUserById(1)->willReturn($user);

        $this->pathCleaner->cleanup(Argument::exact($cleanUpArgument))->shouldBeCalled();
        $this->mediaManager->save($uploadedFile->reveal(), ['locale' => 'en', 'title' => 'my title'], 1);
    }

    public function provideGetByIds()
    {
        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(2);
        $media3 = $this->createMedia(3);

        return [
            [[1, 2, 3], [$media1, $media2, $media3], [$media1, $media2, $media3]],
            [[2, 1, 3], [$media1, $media2, $media3], [$media2, $media1, $media3]],
            [[4, 1, 2], [$media1, $media2], [$media1, $media2]],
        ];
    }

    public function provideSpecialCharacterFileName()
    {
        return [
            ['aäüßa', 'aäüßa'],
            ['aäüßa.mp4', 'aäüßa'],
        ];
    }

    protected function createMedia($id)
    {
        $mediaIdReflection = new \ReflectionProperty(Media::class, 'id');
        $mediaIdReflection->setAccessible(true);

        $media = new Media();
        $mediaIdReflection->setValue($media, $id);

        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersion->setName('Media' . $id);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        return $media;
    }
}
