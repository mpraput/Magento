<?php
namespace Mpr\VideoAdminEditor\Model\Cms\Wysiwyg\Images;

class Storage extends \Magento\Cms\Model\Wysiwyg\Images\Storage
{
    public function uploadFile($targetPath, $type = null)
    {
        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $this->getAllowedExtensions($type);
        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

                
        if (strtolower($uploader->getFileExtension()) !== 'mp4' ) {
            // Create Thumbnail
            $this->resizeFile($targetPath . '/' . $uploader->getUploadedFileName(), true);
        }   

        return $result;
    }
}
