<?php

namespace Terranet\Administrator\Form\Collection;

use Terranet\Administrator\Collection\Mutable as BaseMutableCollection;
use Terranet\Administrator\Columns\MediaElement;
use Terranet\Administrator\Exception;
use Terranet\Administrator\Field\Textarea;
use Terranet\Administrator\Form\FormElement;
use Terranet\Administrator\Form\FormSection;
use Terranet\Administrator\Form\InputFactory;

class Mutable extends BaseMutableCollection
{
    /**
     * Insert a new form element.
     *
     * @param $element
     * @param mixed string|Closure $inputType
     * @param mixed null|int|string $position
     *
     * @throws Exception
     *
     * @return $this
     */
    public function create($element, $inputType = null, $position = null)
    {
        if (!(is_string($element) || $element instanceof FormElement)) {
            throw new Exception('$element must be string or FormElement instance.');
        }

        // Create new element from string declaration ("title").
        if (is_string($element)) {
            $element = (new FormElement($element));
        }

        // Create Form Input Element from string declaration ("textarea")
        if (is_string($inputType)) {
            $oldInput = $element->getInput();
            $newInput = InputFactory::make($element->id(), $inputType);

            $newInput->setRelation(
                $oldInput->getRelation()
            )->setTranslatable(
                $oldInput->getTranslatable()
            );

            $element->setInput(
                $newInput
            );
        }

        // Allow a callable input type.
        if (is_callable($inputType)) {
            call_user_func_array($inputType, [$element]);
        }

        if (is_numeric($position)) {
            return $this->insert($element, $position);
        }

        // Push element
        $this->push($element);

        if (null !== $position) {
            return $this->move($element->id(), $position);
        }

        return $this;
    }

    /**
     * Create a section.
     *
     * @param $section
     * @param null $position
     *
     * @return $this
     */
    public function section($section, $position = null)
    {
        if (is_string($section)) {
            $section = new FormSection($section);
        }

        return null !== $position ? $this->insert($section, $position) : $this->push($section);
    }

    /**
     * Whether the collection has active editor of specific type.
     *
     * @param $editor
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasEditors($editor)
    {
        $this->validateEditor($editor);

        return (bool) $this->filter(function ($field) use ($editor) {
            return $field instanceof Textarea && $field->editorEnabled($editor);
        })->count();
    }

    /**
     * Set rich editors.
     *
     * @param mixed string|array $fields
     */
    public function editors($fields, string $editor = null)
    {
        if (is_array($fields)) {
            foreach ($fields as $field => $editor) {
                $this->editors($field, $editor);
            }
        } elseif (is_string($fields) && $editor) {
            $item = $this->find($fields);
            if ($item instanceof Textarea) {
                if (method_exists($item, $editor)) {
                    $item->$editor();
                }
            }
        }

        return $this;
    }

    /**
     * Set fields descriptions.
     *
     * @param mixed string|array $fields
     */
    public function hints($fields, string $hint = null)
    {
        if (is_array($fields)) {
            foreach ($fields as $field => $hint) {
                $this->hints($field, $hint);
            }
        } elseif (is_string($fields) && $hint) {
            $item = $this->find($fields);
            $item->setDescription($hint);
        }

        return $this;
    }

    /**
     * @param $editor
     *
     * @throws Exception
     */
    protected function validateEditor($editor)
    {
        if (!in_array($editor, ['ckeditor', 'tinymce', 'medium', 'markdown'], true)) {
            throw new Exception(sprintf('Unknown editor %s', $editor));
        }
    }

    /**
     * Create element object from string.
     *
     * @param $element
     *
     * @return mixed
     */
    protected function createElement($element)
    {
        if (is_string($element)) {
            $element = new FormElement($element);
        }

        return $element;
    }

    /**
     * @param $collection
     *
     * @return FormElement|\Terranet\Administrator\Columns\MediaElement
     */
    protected function createMediaElement($collection): MediaElement
    {
        return FormElement::media($collection);
    }
}
