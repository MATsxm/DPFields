<?php
/**
 * @package    DPFields
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2015 - 2016 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPFieldsViewField extends JViewLegacy
{

	protected $form;

	protected $item;

	protected $state;

	public function display ($tpl = null)
	{
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		$section = $this->state->get('field.section') ? $this->state->get('field.section') . '.' : '';
		$this->canDo = JHelperContent::getActions($this->state->get('field.component'), $section . 'field', $this->item->id);

		$input = JFactory::getApplication()->input;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		// Check for tag type
		$this->checkTags = JHelperTags::getTypes('objectList', array(
				$this->state->get('field.context') . '.field'
		), true);

		$input->set('hidemainmenu', true);

		if ($this->getLayout() == 'modal')
		{
			$this->form->setFieldAttribute('language', 'readonly', 'true');
			$this->form->setFieldAttribute('parent_id', 'readonly', 'true');
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function addToolbar ()
	{
		$input = JFactory::getApplication()->input;
		$context = $input->get('context');
		$user = JFactory::getUser();
		$userId = $user->get('id');

		$isNew = ($this->item->id == 0);
		$checkedOut = ! ($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		// Check to see if the type exists
		$ucmType = new JUcmType();
		$this->typeId = $ucmType->getTypeId($context . '.field');

		// Avoid nonsense situation.
		if ($context == 'com_dpfields')
		{
			return;
		}

		// The context can be in the form com_foo.section
		$parts = explode('.', $context);
		$component = $parts[0];
		$section = (count($parts) > 1) ? $parts[1] : null;
		$componentParams = JComponentHelper::getParams($component);

		// Need to load the menu language file as mod_menu hasn't been loaded
		// yet.
		$lang = JFactory::getLanguage();
		$lang->load($component, JPATH_BASE, null, false, true) ||
				 $lang->load($component, JPATH_ADMINISTRATOR . '/components/' . $component, null, false, true);

		// Load the field helper.
		require_once JPATH_COMPONENT . '/helpers/dpfields.php';

		// Get the results for each action.
		$canDo = $this->canDo;

		// If a component fields title string is present, let's use it.
		if ($lang->hasKey(
				$component_title_key = $component . '_FIELDS_' . ($section ? $section : '') . '_FIELD_' . ($isNew ? 'ADD' : 'EDIT') . '_TITLE'))
		{
			$title = JText::_($component_title_key);
		}
		// Else if the component section string exits, let's use it
		else if ($lang->hasKey($component_section_key = $component . '_FIELDS_SECTION_' . ($section ? $section : '')))
		{
			$title = JText::sprintf('COM_DPFIELDS_VIEW_FIELD_' . ($isNew ? 'ADD' : 'EDIT') . '_TITLE',
					$this->escape(JText::_($component_section_key)));
		}
		// Else use the base title
		else
		{
			$title = JText::_('COM_DPFIELDS_VIEW_FIELD_BASE_' . ($isNew ? 'ADD' : 'EDIT') . '_TITLE');
		}

		// Load specific css component
		JHtml::_('stylesheet', $component . '/administrator/fields.css', array(), true);

		// Prepare the toolbar.
		JToolbarHelper::title($title,
				'folder field-' . ($isNew ? 'add' : 'edit') . ' ' . substr($component, 4) . ($section ? "-$section" : '') . '-field-' .
						 ($isNew ? 'add' : 'edit'));

		// For new records, check the create permission.
		if ($isNew)
		{
			JToolbarHelper::apply('field.apply');
			JToolbarHelper::save('field.save');
			JToolbarHelper::save2new('field.save2new');
		}

		// If not checked out, can save the item.
		elseif (! $checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_user_id == $userId)))
		{
			JToolbarHelper::apply('field.apply');
			JToolbarHelper::save('field.save');

			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2new('field.save2new');
			}
		}

		// If an existing item, can save to a copy.
		if (! $isNew && $canDo->get('core.create'))
		{
			JToolbarHelper::save2copy('field.save2copy');
		}

		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('field.cancel');
		}
		else
		{
			if ($componentParams->get('save_history', 0) && $user->authorise('core.edit'))
			{
				$typeAlias = $context . '.field';
				JToolbarHelper::versions($typeAlias, $this->item->id);
			}

			JToolbarHelper::cancel('field.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();

		// Compute the ref_key if it does exist in the component
		if (! $lang->hasKey($ref_key = strtoupper($component . ($section ? "_$section" : '')) . '_FIELD_' . ($isNew ? 'ADD' : 'EDIT') . '_HELP_KEY'))
		{
			$ref_key = 'JHELP_COMPONENTS_' . strtoupper(substr($component, 4) . ($section ? "_$section" : '')) . '_FIELD_' . ($isNew ? 'ADD' : 'EDIT');
		}

		/*
		 * Get help for the field/section view for the component by
		 * -remotely searching in a language defined dedicated URL:
		 * *component*_HELP_URL
		 * -locally searching in a component help file if helpURL param exists
		 * in the component and is set to ''
		 * -remotely searching in a component URL if helpURL param exists in the
		 * component and is NOT set to ''
		 */
		if ($lang->hasKey($lang_help_url = strtoupper($component) . '_HELP_URL'))
		{
			$debug = $lang->setDebug(false);
			$url = JText::_($lang_help_url);
			$lang->setDebug($debug);
		}
		else
		{
			$url = null;
		}

		// JToolbarHelper::help($ref_key, $componentParams->exists('helpURL'),
		// $url, $component);
	}
}
