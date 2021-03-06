<?php
namespace CPSIT\CourseQcatExport\Component\PreProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Benjamin Rannow <b.rannow@familie-redlich.de>
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class PerformanceToQcatArray
 * Maps Performance objects to an array which can
 * be processed to valid Open Qcat XML
 *
 * @package CPSIT\T3importExport\PreProcessor
 */
class PerformanceToQcatArray
    extends AbstractPreProcessor
    implements PreProcessorInterface
{

    /**
     * Tells whether the configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!empty($configuration['class'])) {
            return true;
        }

        return false;
    }

    /**
     * Processes the record
     *
     * @param array $configuration
     * @param \DWenzel\T3events\Domain\Model\Performance $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $performance = $record;
        if (!is_a($performance, $configuration['class'])) {
            return false;
        }

        $record = $this->mapPerformanceToArray($performance, $configuration);

        return true;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function mapPerformanceToArray($performance, $configuration)
    {
        $performanceArray = [];
        $performanceArray['mode'] = 'new';
        $performanceArray['PRODUCT_ID'] = $this->getEntityValueFromPath($performance, 'uid');
        $performanceArray['SUPPLIER_ID_REF']['content'] = $this->getConfigurationValue($configuration,
            'SUPPLIER_ID_REF', 0);
        $performanceArray['SUPPLIER_ID_REF']['type'] = 'supplier_specific';
        $performanceArray['SERVICE_DETAILS'] = $this->getQcatServiceDetailsFromPerformance($performance,
            $configuration);
        $performanceArray['SERVICE_PRICE_DETAILS'] = $this->getQcatServicePriceFromPerformance($performance,
            $configuration);

        return $performanceArray;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServicePriceFromPerformance($performance, $configuration)
    {
        $price = [];

        $priceAmount = $this->getEntityValueFromPath($performance, 'price', 0.0);

        $price['SERVICE_PRICE'] = [
            'PRICE_AMOUNT' => $priceAmount,
            'PRICE_CURRENCY' => 'EUR'
        ];

        $price['REMARKS'] = $this->getEntityValueFromPath($performance, 'priceNotice');

        return $price;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServiceDetailsFromPerformance($performance, $configuration)
    {
        $serviceDetails = [];

        $title = $this->getEntityValueFromPath($performance, 'event.headline');
        $serviceDetails['TITLE'] = trim($title);
        $description = $this->getEntityValueFromPath($performance, 'event.description');
        $description = trim(strip_tags($description));
        $serviceDetails['DESCRIPTION_LONG'] = preg_replace('/&#?[a-z0-9]{2,8};/', '', $description);
        $serviceDetails['SUPPLIER_ALT_PID'] = $this->getConfigurationValue($configuration, 'SUPPLIER_ALT_PID', 0);

        $sample = new \DateTime();
        $startDate = $this->getEntityValueFromPath($performance, 'date', $sample);
        $endDate = $this->getEntityValueFromPath($performance, 'endDate', $sample);

        $serviceDetails['SERVICE_DATE'] = [
            'START_DATE' => $startDate->format(DATE_W3C),
            'END_DATE' => $endDate->format(DATE_W3C)
        ];
        $serviceDetails['KEYWORD'] = GeneralUtility::trimExplode(',',
            $this->getEntityValueFromPath($performance, 'event.keywords'), true);

        $serviceDetails['TARGET_GROUP'] = [
            'TARGET_GROUP_TEXT' => $this->getEntityValueFromPath($performance, 'event.targetgroupRemarks', '')
        ];

        $terms = $this->getEntityValueFromPath($performance, 'event.requirements', '');
        if (!empty($terms)) {
            $serviceDetails['TERMS_AND_CONDITIONS'] = $terms;
        }

        $serviceDetails['SERVICE_MODULE'] = $this->getQcatServiceModuleFromPerformance($performance, $configuration);

        return $serviceDetails;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatServiceModuleFromPerformance($performance, $configuration)
    {
        $education = [];
        /**
         * Gibt an, ob es sich um ein Angebot (true) oder einen Kurs (false) handelt. Angebote sind abstrakte Basisinformationen
         * über Dienstleistungen, die nicht konkret gebucht werden können. Konkrete, buchbare
         * Dienstleistungen können als Veranstaltung deklariert werden, die auf das Basisangebot verweisen. So
         * müssen mehrfach angebotene Dienstleistung nicht redundant im Katalog abgebildet werden. Es ist in
         * diesem Fall nur noch die Angabe der individell gültigen Informationen nötig (z.B. Durchführungsdatum).
         */
        $education['type'] = 'true';
        $education['COURSE_ID'] = $this->getEntityValueFromPath($performance, 'uid');
        /*$education['DEGREE'] = [
            /**
            Öffentlich anerkannt 0
            Befähigungsnachweis 1
            Industriezertifikat 2
            Anbieterspezifisch 3
             */

        /*$education['EXTENDED_INFO'] = [
            /**
            0|Keine Zuordnung möglich
            100|Allgemeinbildende Schule/Einrichtung
            101|Berufsakademie
            102|Berufsbildende Schule/Einrichtung
            103|Berufsbildungswerk
            104|Berufsförderungswerk
            105|Einrichtung der beruflichen Weiterbildung
            106|Fachhochschule
            107|Kunst- und Musikhochschule
            108|Universität
            109|vergleichbare Rehabilitationseinrichtung
            110|med.-berufl. Rehabilitationseinrichtung
             */
        /*	'INSTITUTION' => [
                'type' => '_STATIC_105'
            ],
            /**
            0|Auf Anfrage
            1|Vollzeit
            2|Teilzeit
            3|Wochenendveranstaltung
            4|Fernunterricht/ Fernstudium
            5|Selbststudium/ E-learning/ Blended Learning
            6|Blockunterricht
            7|Inhouse-/ Firmenseminar
             */
        /*	'INSTRUCTION_FORM' => [
                'type' => '_STATIC_0'
            ],
            /**
            0|Keine Zuordnung möglich
            100|Allgemeinbildung
            101|Berufliche Grundqualifikation
            102|Berufsausbildung
            103|Gesetzlich/gesetzesähnlich geregelte Fortbildung/Qualifizierung
            104|Fortbildung/Qualifizierung
            105|Nachholen des Berufsabschlusses
            106|Rehabilitation
            107|Studienangebot - grundständig
            108|Studienangebot - weiterfährend
            109|Umschulung
            110|Integrationssprachkurse (BAMF)
             */
        /*	'EDUCATION_TYPE' => [
                'type' => '_STATIC_104'
            ]

        ];*/

        $education['MODULE_COURSE'] = $this->getQcatModuleCourseFromPerformance($performance, $configuration);

        return ['EDUCATION' => $education];
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    public function getQcatModuleCourseFromPerformance($performance, $configuration)
    {
        $moduleCourse = [];

        $moduleCourse['LOCATION'] = $this->getQcatLocationFromPerformance($performance, $configuration);

        /*$moduleCourse['DURATION'] = [
            /**
            1|bis 3 Tage
            2|mehr als 3 Tage bis 1 Woche
            3|mehr als 1 Woche bis 1 Monat
            4|mehr als 1 Monat bis 3 Monate
            5|mehr als 3 Monate bis 6 Monate
            6|mehr als 6 Monate bis 1 Jahr
            7|mehr als 1 Jahr bis 2 Jahre
            8|mehr als 2 Jahre bis 3 Jahre
            9|mehr als 3 Jahre
            0|Keine Angabe
             */
        /*	'type' => '_STACTIC_0',
            'START_DATE' =>  $performance->getDate()->format(DATE_W3C),
            'END_DATE' =>  $performance->getEndDate()->format(DATE_W3C)
        ];*/

        /*$moduleCourse['EXTENDED_INFO'] = [
            'SEGMENT_TYPE' => [
                /**
                0|Keine Zuordnung
                1|Blockunterricht
                2|Praktikum
                3|Praktikum parallel zu Unterricht
                4|Prüfung
                5|Ferien
                 */

        return $moduleCourse;
    }

    /**
     * @param \DWenzel\T3events\Domain\Model\Performance $performance
     * @param $configuration
     * @return array
     */
    protected function getQcatLocationFromPerformance($performance, $configuration)
    {
        $location = [];

        // todo map in array
        $location['ID_DB'] = $this->getEntityValueFromPath($performance, 'eventLocation.uid');

        $location['NAME'] = substr($this->getEntityValueFromPath($performance, 'eventLocation.name'), 0, 30);
        $location['STREET'] = $this->getEntityValueFromPath($performance, 'eventLocation.address');
        $location['ZIP'] = $this->getEntityValueFromPath($performance, 'eventLocation.zip');
        $location['CITY'] = $this->getEntityValueFromPath($performance, 'eventLocation.place');
        $location['COUNTRY'] = $this->getEntityValueFromPath($performance, 'eventLocation.country.shortNameLocal');

        return $location;
    }

    /**
     * @param AbstractEntity $entity
     * @param $path
     * @param string $default
     * @return mixed|string|AbstractEntity
     */
    protected function getEntityValueFromPath(AbstractEntity $entity, $path, $default = null)
    {
        $value = ObjectAccess::getPropertyPath($entity, $path);
        if (empty($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array $configuration
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function getConfigurationValue($configuration, $key, $default = '')
    {
        if (isset($configuration['fields'])) {
            if (!empty($configuration['fields'][$key])) {

                return $configuration['fields'][$key];
            }
        }

        return $default;
    }

}
