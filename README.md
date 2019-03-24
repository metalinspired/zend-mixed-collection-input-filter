# zend-mixed-collection-input-filter
This collection input filter allows you to define multiple input filters for collection items.
Practical usage would be if you have an array (collection) of items which can have different structure and, thus, would require different input filter to filter/validate each item in collection.

## Example
```php
class ExampleInputFilter extends InputFilter
{
    public function init()
    {
        $this->add((new MixedCollectionInputFilter())
            ->setNameKey('type')
            ->setInputFilters([
                'picture' => $this->picture(),
                'link' => $this->link(),
                'comment' => $this->comment(),
            ])
            ->setFactory($this->getFactory()), 'content');
    }

    private function picture() : InputFilter
    {
        return (new InputFilter())
            ->add(['name' => 'type'])
            ->add(['name' => 'alt'])
            ->add(['name' => 'src']);
    }

    private function link() : InputFilter
    {
        return (new InputFilter())
            ->add(['name' => 'type'])
            ->add(['name' => 'title'])
            ->add(['name' => 'href'])
            ->add(['name' => 'target']);
    }

    private function comment() : InputFilter
    {
        return (new InputFilter())
            ->add(['name' => 'type'])
            ->add(['name' => 'author'])
            ->add(['name' => 'email'])
            ->add(['name' => 'title'])
            ->add(['name' => 'text'])
            ->add([
                'name' => 'notifications',
                'filters' => [
                    ['name' => \Zend\Filter\Boolean::class],
                ],
                'validators' => [
                    [
                        'name' => \Zend\Validator\InArray::class,
                        'options' => [
                            'haystack' => ['0', '1'],
                        ],
                    ],
                ],
            ]);
    }
}

$inputFilter = new ExampleInputFilter();
$inputFilter->init();

$data = [
    'content' => [
        [
            'type' => 'picture',
            'alt' => 'Some picture',
            'src' => 'url',
            'foo' => 'This element will be filtered out',
        ],
        [
            'type' => 'link',
            'href' => 'url',
            'title' => 'Link to something',
            'target' => '_blank',
        ],
        [
            'type' => 'comment',
            'author' => 'unknown',
            'email' => 'dummy@email.com',
            'title' => 'Example',
            'text' => 'Got nothing more to say',
            'notifications' => '1',
        ],
        [
            'type' => 'picture',
            'alt' => 'Another picture',
            'src' => 'another url',
        ],
    ],
];

$inputFilter->setData($data);

if ($inputFilter->isValid()) {
    var_dump($inputFilter->getValues());
} else {
    var_dump($inputFilter->getMessages());
}
```