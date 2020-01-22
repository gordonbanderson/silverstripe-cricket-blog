<?php

namespace Suilven\CricketSite\Task;

use League\Flysystem\Filesystem;
use ReflectionMethod;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Class DeleteGeneratedImagesTask
 *
 * Hack to allow removing manipulated images
 * This is needed occasionally when manipulation functions change
 * It isn't directly possible with core so this is a workaround
 *
 * @see https://github.com/silverstripe/silverstripe-assets/issues/109
 * @package App\Tasks
 * @codeCoverageIgnore
 */
class DeleteGeneratedImagesTask extends BuildTask
{

    public function getDescription(): string
    {
        return 'Regenerate Images for an asset';
    }

    /**
     * Create test jobs for the purposes of testing.
     *
     * @param HTTPRequest $request
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function run($request) // phpcs:ignore
    {
        // only allow for admins
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $Id = $request->getVar('ID');
        if (!$Id) {
            echo 'No ID provided, make sure to supply an ID to the URL eg ?ID=2';
            return;
        }
        $image = DataObject::get_by_id(Image::class, $Id);

        if (!$image) {
            echo 'No Image found with that ID';
            return;
        }
        $asetValues = $image->File->getValue();
        $store = Injector::inst()->get(AssetStore::class);

        // warning - super hacky as accessing private methods
        $getID = new ReflectionMethod(FlysystemAssetStore::class, 'getFileID');
        $getID->setAccessible(true);
        $flyID = $getID->invoke($store, $asetValues['Filename'], $asetValues['Hash']);
        $getFileSystem = new ReflectionMethod(FlysystemAssetStore::class, 'getFilesystemFor');
        $getFileSystem->setAccessible(true);
        /** @var Filesystem $system */
        $system = $getFileSystem->invoke($store, $flyID);

        $findVariants = new ReflectionMethod(FlysystemAssetStore::class, 'findVariants');
        $findVariants->setAccessible(true);
        foreach ($findVariants->invoke($store, $flyID, $system) as $variant) {
            $isGenerated = strpos($variant, '__');
            if (!$isGenerated) {
                continue;
            }
            $system->delete($variant);
        }
        echo "Deleted generated images for $image->Name";
    }
}
