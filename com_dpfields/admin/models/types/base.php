<?php
/**
 * @package    DPFields
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2015 - 2015 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPFieldsTypeBase
{

	/**
	 * Returns a XML field tag for that type which can be placed in a form.
	 *
	 * @param stdClass $field
	 * @param DOMElement $parent
	 * @return DOMElement
	 */
	public function appendXMLFieldTag ($field, DOMElement $parent)
	{
		$app = JFactory::getApplication();
		if ($field->params->get('show_on') == 1 && $app->isAdmin())
		{
			return;
		}
		else if ($field->params->get('show_on') == 2 && $app->isSite())
		{
			return;
		}
		$node = $parent->appendChild(new DOMElement('field'));

		$node->setAttribute('name', $field->alias);
		$node->setAttribute('type', $field->type);
		$node->setAttribute('default', $field->default_value);
		$node->setAttribute('label', $field->label);
		$node->setAttribute('description', $field->description);
		$node->setAttribute('class', $field->class);
		$node->setAttribute('required', $field->required ? 'true' : 'false');
		$node->setAttribute('readonly', $field->params->get('readonly', 0) ? 'true' : 'false');

		// Set the disabled state based on the parameter and the permission
		$authorizedToEdit = JFactory::getUser()->authorise('edit.value', $field->context . '.field.' . (int) $field->id);
		$node->setAttribute('disabled', $field->params->get('disabled', 0) || ! $authorizedToEdit ? 'true' : 'false');

		foreach ($field->fieldparams->toArray() as $key => $param)
		{
			if (is_array($param))
			{
				$param = implode(',', $param);
			}
			$node->setAttribute($key, $param);
		}
		$this->postProcessDomNode($field, $node);

		return $node;
	}

	/**
	 * Prepares the given value to be ready to be displayed in a HTML context.
	 *
	 * @param stdClass $field
	 * @param mixed $value
	 * @return string
	 */
	public function prepareValueForDisplay ($value, $field)
	{
		if (is_array($value))
		{
			$value = implode(', ', $value);
		}
		return htmlentities($value);
	}

	/**
	 * Function to manipulate the DOM node before it is returned to the form
	 * document.
	 *
	 * @param stdClass $field
	 * @param DOMElement $fieldNode
	 */
	protected function postProcessDomNode ($field, DOMElement $fieldNode)
	{
	}
}