<?php

namespace DclPictureExport\business;
use ilDclContentExporter;

require_once 'Modules/DataCollection/classes/Content/class.ilDclContentExporter.php';
require_once 'Services/MediaObjects/classes/class.ilObjMediaObject.php';

/**
 * Class ExcelExport
 *
 * Excel exporter which ensures unique file naming.
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 */
class ExcelExport extends \ilDclContentExporter
{
    const MEDIA_OBJECT_DATA_TYPE_TITLE = "mob";

    /**
     * @var $fileNameSet array
     */
    private $fileNameSet = [];

    /**
     * @var $exportPath string
     */
    private $exportPath;

    /**
     * Export a table as excel with all pictures.
     * The result will be send as zip to the client.
     *
     * @return void
     */
    public function export($format = self::EXPORT_EXCEL, $filepath = null, $send = false)
    {
        parent::export(self::EXPORT_EXCEL, null, false);
        $this->sendZip();
        $this->cleanUpExportPath();
    }

    protected function fillRowExcel(\ilDclTable $table, \ilExcel $worksheet, \ilDclBaseRecordModel $record, $row)
    {
        $col = 0;
        foreach ($table->getFields() as $field) {
            if ($field->getExportable()) {

                //check if we need to handle the object
                if($this->isMediaObject($field))
                {
                    //fetch record
                    $recordField = $record->getRecordField($field->getId());
                    $fieldContent = $recordField->getValue();

                    //get media object
                    $mediaObject = new \ilObjMediaObject($fieldContent, false);
                    $effectiveFileName = $this->copyMediaObjectToZipDirectory($mediaObject);

                    //copy media
                    $this->rememberMediaObjectName($effectiveFileName);

                    //set field value for the excel export
                    $recordField->setValue($effectiveFileName, true);
                }

                $record->fillRecordFieldExcelExport($worksheet, $row, $col, $field->getId());
            }
        }
    }

    public function getExportContentPath($format = self::EXPORT_EXCEL)
    {
        if(!$this->exportPath)
        {
            $path = parent::getExportContentPath($format);
            $path .= ("zip_picture_export_" . time() . "/");
            $this->exportPath = $path;
        }

        return $this->exportPath;
    }


    /**
     * Store the media object name.
     * This names are used to detect identical named objects.
     * If the given field is not a media object, no further actions are taken.
     *
     * @param string $mediaObjectName The media object which should be stored in the name set.
     */
    private function rememberMediaObjectName($mediaObjectName)
    {
            //add name to "set"
            $this->fileNameSet[$mediaObjectName] = null;
    }

    /**
     * Checks if the given field is already known by the excel exporter.
     *
     * @param \ilObjMediaObject|string $media    Media object which should be checked.
     *
     * @return bool If the field name is already known this method returns true otherwise false.
     */
    private function isMediaObjectAlreadyKnown($media)
    {
        $mediaName = (is_string($media)) ? $media : $media->getTitle();
        return array_key_exists($mediaName, $this->fileNameSet);
    }

    /**
     * Renames a field to ensure a unique field name.
     * If the field is already known, a number will be appended.
     * The limitation of this method is 2^64 base field names.
     * Naming schema: {base_name}_{counter}{file ending (eg. .jpg)}
     *
     * @param \ilObjMediaObject $media
     *
     * @return string
     */
    private function createUniqueMediaObjectName(\ilObjMediaObject $media)
    {
        if(!$this->isMediaObjectAlreadyKnown($media))
            return $media->getTitle();

        $mediaName = $media->getTitle();

        //get suffix
        $matches = [];
        preg_match('/(?<suffix>\..+$)/', $mediaName, $matches);
        $suffix = array_key_exists("suffix", $matches) ? $matches["suffix"] : "";

        $counter = 0;
        $fieldNameBase = str_replace($suffix, "", $mediaName);

        //rename field as long as we know it.
        do
        {
            $mediaName = "{$fieldNameBase}_{$counter}{$suffix}";
            ++$counter;
        }
        while ($this->isMediaObjectAlreadyKnown($mediaName));

        return $mediaName;
    }

    /**
     * Checks if the given field is a media object or not.
     *
     * @param \ilDclBaseFieldModel $field The field which should be type checked.
     *
     * @return bool Returns true if the given field is a media object otherwise false.
     */
    private function isMediaObject(\ilDclBaseFieldModel $field)
    {
        $datatype = $field->getDatatype()->getTitle();
        return strcmp($datatype, self::MEDIA_OBJECT_DATA_TYPE_TITLE) === 0;
    }

    /**
     * Copy the media that matches the title of the media object to the export directory.
     * The media name is modified to ensure an unique name.
     *
     * @param \ilObjMediaObject $media  The media object which should be exported.
     *
     * @return string Effective file name stored in the zip file.
     */
    private function copyMediaObjectToZipDirectory(\ilObjMediaObject $media)
    {
        $originalName = $media->getTitle();
        $uniqueName = $this->createUniqueMediaObjectName($media);
        $exportPath = $this->getExportContentPath(self::EXPORT_EXCEL) . "/{$uniqueName}";
        $mediaDirectory = \ilUtil::getWebspaceDir() . "/mobs/mm_" . $media->getId() . "/{$originalName}";
        copy($mediaDirectory, $exportPath);

        return $uniqueName;
    }

    /**
     * Compress all the data and sends the zip to the requester.
     * The zip will be deleted afterwards.
     */
    private function sendZip()
    {
        $zipName = time() . '__' . $this->tables[0]->getTitle() . ".zip";
        $zipStorageDir = parent::getExportContentPath(self::EXPORT_EXCEL);
        $zip = "{$zipStorageDir}/{$zipName}";
        \ilUtil::zip($this->getExportContentPath(), $zip);
        \ilUtil::deliverFile($zip, $zipName, 'application/x-7z-compressed', false, true, false);
    }

    /**
     * Cleans the export path to free the used storage.
     */
    private function cleanUpExportPath()
    {
        \ilUtil::delDir($this->exportPath);
    }
}