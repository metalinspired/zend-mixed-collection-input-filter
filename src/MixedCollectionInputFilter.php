<?php
/**
 * @author    Milan Divkovic <metalinspired@gmail.com>
 * @license   https://opensource.org/licenses/BSD-3-Clause New BSD License
 */

namespace metalinspired\MixedCollectionInputFilter;

use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\Exception;
use Zend\InputFilter\InputFilter;
use Zend\Validator\NotEmpty;

class MixedCollectionInputFilter extends InputFilter
{
    const MISSING_NAME_KEY = 'Missing name key for entry',
        MISSING_FILTER = 'Missing filter for entry';

    /**
     * @var bool
     */
    protected $isRequired = false;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var array[]
     */
    protected $collectionValues = [];

    /**
     * @var array[]
     */
    protected $collectionRawValues = [];

    /**
     * @var array
     */
    protected $collectionMessages = [];

    /**
     * @var NotEmpty
     */
    protected $notEmptyValidator;

    /**
     * @var string
     */
    protected $nameKey;

    /**
     * @var BaseInputFilter[]
     */
    protected $inputFilters = [];

    /**
     * @var bool
     */
    protected $nameKeyMissingInvalid = false;

    /**
     * @var bool
     */
    protected $filterMissingInvalid = false;

    /**
     * @param string $name
     * @return MixedCollectionInputFilter
     */
    public function setNameKey(string $name) : MixedCollectionInputFilter
    {
        $this->nameKey = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameKey() : string
    {
        return $this->nameKey;
    }

    /**
     * @param $inputFilters
     * @return MixedCollectionInputFilter
     */
    public function setInputFilters($inputFilters) : MixedCollectionInputFilter
    {
        if (\is_array($inputFilters) || $inputFilters instanceof \Traversable) {
            foreach ($inputFilters as $name => $inputFilter) {
                if (! \is_string($name)) {
                    throw new Exception\RuntimeException('Input filter key is not string');
                }

                if (\is_array($inputFilter) || $inputFilter instanceof \Traversable) {
                    $inputFilter = $this->getFactory()->createInputFilter($inputFilter);
                }

                if (! $inputFilter instanceof BaseInputFilter) {
                    throw new Exception\RuntimeException(sprintf(
                        '%s expects an instance of %s; received "%s"',
                        __METHOD__,
                        BaseInputFilter::class,
                        (\is_object($inputFilter) ? \get_class($inputFilter) : \gettype($inputFilter))
                    ));
                }

                $this->inputFilters[$name] = $inputFilter;
            }
        }

        return $this;
    }

    /**
     * @return BaseInputFilter[]
     */
    public function getInputFilters() : array
    {
        return $this->inputFilters;
    }

    /**
     * Get the input filter used when looping the data
     *
     * @param string $name
     * @return BaseInputFilter|null
     */
    protected function getInputFilter(string $name)
    {
        if (! isset($this->inputFilters[$name])) {
            return null;
        }

        return $this->inputFilters[$name];
    }

    /**
     * @param bool $invalid
     * @return MixedCollectionInputFilter
     */
    public function setNameKeyMissingInvalid(bool $invalid) : MixedCollectionInputFilter
    {
        $this->nameKeyMissingInvalid = $invalid;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNameKeyMissingInvalid() : bool
    {
        return $this->nameKeyMissingInvalid;
    }

    /**
     * @param bool $invalid
     * @return MixedCollectionInputFilter
     */
    public function setFilterMissingInvalid(bool $invalid) : MixedCollectionInputFilter
    {
        $this->filterMissingInvalid = $invalid;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFilterMissingInvalid() : bool
    {
        return $this->filterMissingInvalid;
    }

    /**
     * Set if the collection can be empty
     *
     * @param bool $isRequired
     * @return MixedCollectionInputFilter
     */
    public function setIsRequired($isRequired) : MixedCollectionInputFilter
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get if collection can be empty
     *
     * @return bool
     */
    public function getIsRequired() : bool
    {
        return $this->isRequired;
    }

    /**
     * Set the count of data to validate
     *
     * @param int $count
     * @return MixedCollectionInputFilter
     */
    public function setCount(int $count) : MixedCollectionInputFilter
    {
        $this->count = $count > 0 ? $count : 0;

        return $this;
    }

    /**
     * Get the count of data to validate, use the count of data by default
     *
     * @return int
     */
    public function getCount() : int
    {
        if (null === $this->count) {
            return \count($this->data);
        }

        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        if (! (\is_array($data) || $data instanceof \Traversable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable collection; invalid collection of type %s provided',
                __METHOD__,
                \is_object($data) ? \get_class($data) : \gettype($data)
            ));
        }

        $this->setUnfilteredData($data);

        foreach ($data as $item) {
            if (\is_array($item) || $item instanceof \Traversable) {
                continue;
            }

            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects each item in a collection to be an array or Traversable; '
                . 'invalid item in collection of type %s detected',
                __METHOD__,
                \is_object($item) ? \get_class($item) : \gettype($item)
            ));
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Retrieve the NotEmpty validator to use for failed "required" validations.
     *
     * This validator will be used to produce a validation failure message in
     * cases where the collection is empty but required.
     *
     * @return NotEmpty
     */
    public function getNotEmptyValidator() : NotEmpty
    {
        if (null === $this->notEmptyValidator) {
            $this->notEmptyValidator = new NotEmpty();
        }

        return $this->notEmptyValidator;
    }

    /**
     * Set the NotEmpty validator to use for failed "required" validations.
     *
     * This validator will be used to produce a validation failure message in
     * cases where the collection is empty but required.
     *
     * @param NotEmpty $notEmptyValidator
     * @return MixedCollectionInputFilter
     */
    public function setNotEmptyValidator(NotEmpty $notEmptyValidator) : MixedCollectionInputFilter
    {
        $this->notEmptyValidator = $notEmptyValidator;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @param mixed $context Ignored, but present to retain signature compatibility.
     */
    public function isValid($context = null) : bool
    {
        $this->collectionMessages = [];
        $valid = true;

        if ($this->isRequired && $this->getCount() < 1) {
            $this->collectionMessages[] = $this->prepareRequiredValidationFailureMessage();
            $valid = false;
        }

        if (\count($this->data) < $this->getCount()) {
            $valid = false;
        }

        if (! $this->data) {
            $this->clearValues();
            $this->clearRawValues();

            return $valid;
        }

        foreach ($this->data as $key => $data) {
            if (! isset($data[$this->nameKey])) {
                if ($this->nameKeyMissingInvalid) {
                    $valid = false;
                    $this->collectionMessages[$key] = self::MISSING_NAME_KEY;
                }

                continue;
            }

            $inputFilter = $this->getInputFilter($data[$this->nameKey]);

            if (! $inputFilter) {
                if ($this->filterMissingInvalid) {
                    $valid = false;
                    $this->collectionMessages[$key] = self::MISSING_FILTER;
                }

                continue;
            }

            $inputFilter->setData($data);

            if (null !== $this->validationGroup) {
                $inputFilter->setValidationGroup($this->validationGroup[$key]);
            }

            if ($inputFilter->isValid()) {
                $this->validInputs[$key] = $inputFilter->getValidInput();
            } else {
                $valid = false;
                $this->collectionMessages[$key] = $inputFilter->getMessages();
                $this->invalidInputs[$key] = $inputFilter->getInvalidInput();
            }

            $this->collectionValues[$key] = $inputFilter->getValues();
            $this->collectionRawValues[$key] = $inputFilter->getRawValues();
        }

        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidationGroup($name) : MixedCollectionInputFilter
    {
        if ($name === self::VALIDATE_ALL) {
            $name = null;
        }
        $this->validationGroup = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues() : array
    {
        return $this->collectionValues;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawValues() : array
    {
        return $this->collectionRawValues;
    }

    /**
     * Clear collectionValues
     *
     * @return array[]
     */
    public function clearValues() : array
    {
        return $this->collectionValues = [];
    }

    /**
     * Clear collectionRawValues
     *
     * @return array[]
     */
    public function clearRawValues() : array
    {
        return $this->collectionRawValues = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages() : array
    {
        return $this->collectionMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnknown() : array
    {
        if (! $this->data) {
            throw new Exception\RuntimeException(sprintf(
                '%s: no data present!',
                __METHOD__
            ));
        }

        $unknownInputs = [];

        foreach ($this->data as $key => $data) {
            if (! isset($data[$this->nameKey])) {
                $unknownInputs[$key] = self::MISSING_NAME_KEY;
                continue;
            }

            $inputFilter = $this->getInputFilter($data[$this->nameKey]);

            if (! $inputFilter) {
                $unknownInputs[$key] = self::MISSING_FILTER;
                continue;
            }

            $inputFilter->setData($data);

            $unknown = $inputFilter->getUnknown();

            if ($unknown) {
                $unknownInputs[$key] = $unknown;
            }
        }

        return $unknownInputs;
    }

    /**
     * @return array<string, string>
     */
    protected function prepareRequiredValidationFailureMessage() : array
    {
        $notEmptyValidator = $this->getNotEmptyValidator();
        $templates = $notEmptyValidator->getOption('messageTemplates');
        $message = $templates[NotEmpty::IS_EMPTY];
        $translator = $notEmptyValidator->getTranslator();

        return [
            NotEmpty::IS_EMPTY => $translator
                ? $translator->translate($message, $notEmptyValidator->getTranslatorTextDomain())
                : $message,
        ];
    }
}
