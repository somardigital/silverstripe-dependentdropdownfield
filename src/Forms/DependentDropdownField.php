<?php

namespace Sheadawson\DependentDropdown\Forms;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\Map;
use SilverStripe\View\Requirements;

/**
 * Class DependentDropdownField
 *
 * A dropdown that depends on another dropdown for populating values, and calls
 * a callback when that dropdown is updated.
 *
 * @package SilverStripe\Forms
 */
class DependentDropdownField extends DropdownField
{
    use DependentFieldTrait;

    /**
     * DependentDropdownField constructor.
     * @param string $name
     * @param string $title
     * @param \Closure $source
     * @param string $value
     * @param $form
     * @param string $emptyString
     */
    public function __construct($name, $title = null, \Closure $source = null, $value = '', $form = null, $emptyString = null)
    {
        parent::__construct($name, $title, [], $value, $form, $emptyString);

        // we are unable to store Closure as a normal source
        $this->sourceCallback = $source;
        $this
            ->addExtraClass('dependent-dropdown')
            ->addExtraClass('dropdown');
    }

    /**
     * @return array|\ArrayAccess|mixed
     */
    public function getSource()
    {
        $val = $this->depends->Value();

        if (
            !$val
            && method_exists($this->depends, 'getHasEmptyDefault')
            && !$this->depends->getHasEmptyDefault()
        ) {
            $dependsSource = array_keys($this->depends->getSource());
            $val = isset($dependsSource[0]) ? $dependsSource[0] : null;
        }

        if (!$val) {
            $source = [];
        } else {
            $source = call_user_func($this->sourceCallback, $val);
            if ($source instanceof Map) {
                $source = $source->toArray();
            }
        }

        if ($this->getHasEmptyDefault()) {
            return ['' => $this->getEmptyString()] + (array) $source;
        } else {
            return $source;
        }
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        if (!is_subclass_of(Controller::curr(), LeftAndMain::class)) {
            Requirements::javascript('silverstripe/admin:thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
        }

        Requirements::javascript(
            'sheadawson/silverstripe-dependentdropdownfield:client/js/dependentdropdownfield.js'
        );

        $this->setAttribute('data-link', $this->Link('load'));
        $this->setAttribute('data-depends', $this->getDepends()->getName());
        $this->setAttribute('data-empty', $this->getEmptyString());
        $this->setAttribute('data-unselected', $this->getUnselectedString());

        return parent::Field($properties);
    }
}
