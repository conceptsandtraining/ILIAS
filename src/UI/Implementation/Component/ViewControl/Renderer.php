<?php
/* Copyright (c) 2017 Jesús López <lopez@leifos.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\ViewControl;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;

/**
 * Class Renderer
 * @package ILIAS\UI\Implementation\Component\ViewControl
 */
class Renderer extends AbstractComponentRenderer
{
	const MODE_ROLE = "group";

	/**
	 * @param Component\Component $component
	 * @param RendererInterface $default_renderer
	 */
	public function render(Component\Component $component, RendererInterface $default_renderer)
	{
		$this->checkComponent($component);

		if ($component instanceof Component\ViewControl\Mode) {
			return $this->renderMode($component, $default_renderer);
		}
		if ($component instanceof Component\ViewControl\Section) {
			return $this->renderSection($component, $default_renderer);
		}
		if ($component instanceof Component\ViewControl\Sortation) {
			return $this->renderSortation($component, $default_renderer);
		}
		if ($component instanceof Component\ViewControl\Pagination) {
			return $this->renderPagination($component, $default_renderer);
		}
		if ($component instanceof Component\ViewControl\Quickfilter) {
			return $this->renderQickfilter($component, $default_renderer);

		}
	}

	protected function renderMode(Component\ViewControl\Mode $component, RendererInterface $default_renderer)
	{
		$f = $this->getUIFactory();

		$tpl = $this->getTemplate("tpl.mode.html", true, true);

		$active = $component->getActive();
		if($active == "") {
			$activate_first_item = true;
		}

		foreach ($component->getLabelledActions() as $label => $action)
		{
			$tpl->setVariable("ARIA", $this->txt($component->getAriaLabel()));
			$tpl->setVariable("ROLE", self::MODE_ROLE);

			$tpl->setCurrentBlock("view_control");

			//At this point we don't have an specific text for the button aria label. component->getAriaLabel gets the main viewcontrol aria label.
			if($activate_first_item) {
				$tpl->setVariable("BUTTON", $default_renderer->render($f->button()->standard($label, $action)->WithUnavailableAction()->withAriaLabel($label)->withAriaChecked()));
				$activate_first_item = false;
			} else if($active == $label) {
				$tpl->setVariable("BUTTON", $default_renderer->render($f->button()->standard($label, $action)->withUnavailableAction()->withAriaLabel($label)->withAriaChecked()));
			}
			else {
				$tpl->setVariable("BUTTON", $default_renderer->render($f->button()->standard($label, $action)->withAriaLabel($label)));
			}
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}

	protected function renderSection(Component\ViewControl\Section $component, RendererInterface $default_renderer)
	{
		$f = $this->getUIFactory();

		$tpl = $this->getTemplate("tpl.section.html", true, true);

		// render middle button
		$tpl->setVariable("BUTTON", $default_renderer->render($component->getSelectorButton()));

		// previous button
		$this->renderSectionButton($component->getPreviousActions(), $tpl, "prev");

		// next button
		$this->renderSectionButton($component->getNextActions(), $tpl, "next");

		return $tpl->get();
	}

	/**
	 * @param Component\Button\Button $component button
	 * @param $tpl
	 * @param string $type
	 */
	protected function renderSectionButton(Component\Button\Button $component, $tpl, $type)
	{
		$uptype = strtoupper($type);

		$action = $component->getAction();
		$tpl->setVariable($uptype."_ACTION", $action);
		if ($component->isActive())
		{
			$tpl->setCurrentBlock($type."_with_href");
			$tpl->setVariable($uptype."_HREF", $action);
			$tpl->parseCurrentBlock();
		} else {
			$tpl->touchBlock($type."_disabled");
		}
		$this->maybeRenderId($component, $tpl, $type."_with_id", $uptype."_ID");
	}


	protected function renderSortation(Component\ViewControl\Sortation $component, RendererInterface $default_renderer) {
		$f = new \ILIAS\UI\Implementation\Factory(
			new \ILIAS\UI\Implementation\Component\SignalGenerator()
		);

		$tpl = $this->getTemplate("tpl.sortation.html", true, true);

		$component = $component->withResetSignals();
		$triggeredSignals = $component->getTriggeredSignals();
		if($triggeredSignals) {

			$internal_signal = $component->getSelectSignal();
			$signal = $triggeredSignals[0]->getSignal();
			$options = json_encode($signal->getOptions());

			$component = $component->withOnLoadCode(function($id) use ($internal_signal, $signal) {
				return "$(document).on('{$internal_signal}', function(event, signalData) {
							il.UI.viewcontrol.sortation.onInternalSelect(event, signalData, '{$signal}', '{$id}');
							return false;
						})";
			});

			//maybeRenderId does not return id
			$id = $this->bindJavaScript($component);
			$tpl->setVariable('ID', $id);
		}

		//setup entries
		$options = $component->getOptions();
		$init_label = $component->getLabel();
		$items = array();
		foreach ($options as $val => $label) {
			if($triggeredSignals) {
				$shy = $f->button()->shy($label, $val)->withOnClick($internal_signal);
			} else {
				$url = $component->getTargetURL();
				$url .= (strpos($url, '?') === false) ?  '?' : '&';
				$url .= $component->getParameterName() .'=' .$val;
				$url = $this->appendCurrentGetParamters($url);
				$shy = $f->button()->shy($label, $url);
			}
			$items[] = $shy;
		}

		$dd = $f->dropdown()->standard($items)
			->withLabel($init_label);

		$tpl->setVariable('SORTATION_DROPDOWN', $default_renderer->render($dd));
		return $tpl->get();
	}



	protected function renderPagination(Component\ViewControl\Pagination $component, RendererInterface $default_renderer) {
		$tpl = $this->getTemplate("tpl.pagination.html", true, true);

		$component = $component->withResetSignals();
		$triggeredSignals = $component->getTriggeredSignals();
		if($triggeredSignals) {
			$internal_signal = $component->getInternalSignal();
			$signal = $triggeredSignals[0]->getSignal();
			$component = $component->withOnLoadCode(function($id) use ($internal_signal, $signal) {
				return "$(document).on('{$internal_signal}', function(event, signalData) {
							il.UI.viewcontrol.pagination.onInternalSelect(event, signalData, '{$signal}', '{$id}');
							return false;
						})";
			});

			$id = $this->bindJavaScript($component);
			$tpl->setVariable('ID', $id);
		}

		$range = $this->getPaginationRange($component);
		$chunk_options = array();
		foreach ($range as $entry) {
			$shy = $this->getPaginationShyButton($entry, $component);
			if((int)$entry === $component->getCurrentPage()) {
				$shy = $shy->withUnavailableAction();
			}
			$chunk_options[] = $shy;
		}

		if( $component->getDropdownAt() == null ||
			$component->getDropdownAt() > $component->getNumberOfPages()) {

			foreach ($chunk_options as $entry) {
				$tpl->setCurrentBlock("entry");
				$tpl->setVariable('BUTTON',	$default_renderer->render($entry));
				$tpl->parseCurrentBlock();
			}

		}else {
			//if threshold is reached, render as dropdown
			$f = new \ILIAS\UI\Implementation\Factory(
				new \ILIAS\UI\Implementation\Component\SignalGenerator()
			);
			$dd = $f->dropdown()->standard($chunk_options)->withLabel(
				(string)($component->getCurrentPage() + 1)
			);
			$tpl->setCurrentBlock("entry");
			$tpl->setVariable('BUTTON',	$default_renderer->render($dd));
			$tpl->parseCurrentBlock();
		}

		if($component->getMaxPaginationButtons()) {
 			$this->setPaginationFirstLast($component, $range, $default_renderer, $tpl);
		}

		$this->setPaginationRockers($component, $default_renderer, $tpl);
		return $tpl->get();
	}

	/**
	 * Get the range of pagintaion-buttons to show.
	 *
	 * @param Component\ViewControl\Pagination 	$component
	 *
	 * @return  int[]
	 */
	protected function getPaginationRange(Component\ViewControl\Pagination $component) {
		if(! $component->getMaxPaginationButtons()) {
			$start = 0;
			$stop = max($component->getNumberOfPages() - 1, 0);
		} else {
			//current page should be in the middle, so start is half the amount of max entries:
			$start = (int) ($component->getCurrentPage() - floor($component->getMaxPaginationButtons() / 2));
			$start = max($start, 0); //0, if negative
			//stop is (calculated) start plus number of entries:
			$stop = $start + $component->getMaxPaginationButtons() - 1;
			//if stop exceeds max pages, recalculate both:
			if($stop > $component->getNumberOfPages() - 1) {
				$stop = max($component->getNumberOfPages() - 1, 0); //0, if negative
				$start = $stop - $component->getMaxPaginationButtons();
				$start = max($start, 0); //0, if negative
			}
		}
		return range($start, $stop);
	}


	protected function renderQickfilter(Component\ViewControl\Quickfilter $component, RendererInterface $default_renderer) {
		$f = new \ILIAS\UI\Implementation\Factory(
			new \ILIAS\UI\Implementation\Component\SignalGenerator()
		);

		$tpl = $this->getTemplate("tpl.quickfilter.html", true, true);

		$component = $component->withResetSignals();
		$triggeredSignals = $component->getTriggeredSignals();
		if($triggeredSignals) {

			$internal_signal = $component->getSelectSignal();
			$signal = $triggeredSignals[0]->getSignal();
			$options = json_encode($signal->getOptions());

			$component = $component->withOnLoadCode(function($id) use ($internal_signal, $signal) {
				return "$(document).on('{$internal_signal}', function(event, signalData) {
							il.UI.viewcontrol.quickfilter.onInternalSelect(event, signalData, '{$signal}', '{$id}');
							return false;
						})";
			});

			//maybeRenderId does not return id
			$id = $this->bindJavaScript($component);
			$tpl->setVariable('ID', $id);
		}

		//setup entries
		$options = $component->getOptions();
		$init_label = $component->getLabel();
		$items = array();
		foreach ($options as $val => $label) {
			if($triggeredSignals) {
				$shy = $f->button()->shy($label, $val)->withOnClick($internal_signal);
			} else {
				$url = $component->getTargetURL();
				$url .= (strpos($url, '?') === false) ?  '?' : '&';
				$url .= $component->getParameterName() .'=' .$val;
				$url = $this->appendCurrentGetParamters($url);
				$shy = $f->button()->shy($label, $url);
			}
			$items[] = $shy;
		}

		if(array_key_exists($component->getParameterName(), $_GET)) {
			$default_value = $component->getDefaultValue();

			if($default_value != $_GET[$component->getParameterName()]) {
				$init_label = $options[$_GET[$component->getParameterName()]];
			}
		}

		$dd = $f->dropdown()->standard($items)
			->withLabel($init_label);

		$tpl->setVariable('QUICKFILTER_DROPDOWN', $default_renderer->render($dd));
		return $tpl->get();
	}


	/**
	 * @param string 	$val
	 * @param Component\ViewControl\Pagination 	$component
	 * @param string 	$label
	 *
	 * @return \ILIAS\UI\Component\Button\Shy
	 */
	protected function getPaginationShyButton($val, Component\ViewControl\Pagination $component, $label='') {
		$f = new \ILIAS\UI\Implementation\Factory(
			new \ILIAS\UI\Implementation\Component\SignalGenerator()
		);
		if($label === '') {
			$label = (string)($val+1);
		}

		if($component->getTriggeredSignals()) {
			$shy = $f->button()->shy($label, (string)$val)->withOnClick($component->getInternalSignal());
		} else {
			$url = $component->getTargetURL();
			if(strpos($url, '?') === false) {
				$url .= '?' .$component->getParameterName() .'=' .$val;
			} else {
				$base = substr($url, 0, strpos($url, '?') + 1);
				$query = parse_url($url, PHP_URL_QUERY);
				parse_str($query, $params);
				$params[$component->getParameterName()] = $val;
				$url = $base .http_build_query($params);
			}
			$shy = $f->button()->shy($label, $url);
		}
		return $shy;
	}

	/**
	 * Add chevron-rockers to the template for left/right navigation in pagination
	 *
	 * @param Component\ViewControl\Pagination 	$component
	 * @param RendererInterface $default_renderer 	$default_renderer
	 * @param ILIAS\UI\Implementation\Render\Template 	$tpl
	 *
	 * @return void
	 */
	protected function setPaginationRockers(Component\ViewControl\Pagination $component, RendererInterface $default_renderer, &$tpl) {
		$prev = max(0, $component->getCurrentPage() - 1);
		$shy_left = $this->getPaginationShyButton(
			(string)$prev,
			$component,
			'<span class="glyphicon glyphicon-chevron-left"></span>'
		);
		if($component->getCurrentPage() === 0) {
			$shy_left = $shy_left->WithUnavailableAction();
		}

		$next = $component->getCurrentPage() + 1;
		$shy_right = $this->getPaginationShyButton(
			(string)$next,
			$component,
			'<span class="glyphicon glyphicon-chevron-right"></span>'
		);
		if($component->getCurrentPage() >= $component->getNumberOfPages()-1) {
			$shy_right = $shy_right->WithUnavailableAction();
		}

		$tpl->setVariable('ROCKER_PREVIOUS', $default_renderer->render($shy_left));
		$tpl->setVariable('ROCKER_NEXT', $default_renderer->render($shy_right));
	}

	/**
	 * Add quick-access to first/last pages in pagination.
	 *
	 * @param Component\ViewControl\Pagination 	$component
	 * @param int[]	$range
	 * @param RendererInterface $default_renderer 	$default_renderer
	 * @param ILIAS\UI\Implementation\Render\Template 	$tpl
	 *
	 * @return void
	 */
	protected function setPaginationFirstLast(Component\ViewControl\Pagination $component, $range, RendererInterface $default_renderer, &$tpl) {
		if(! in_array(0, $range)) {
			$shy = $this->getPaginationShyButton(0, $component);
			$tpl->setVariable('FIRST', $default_renderer->render($shy));
		}
		$last = max($component->getNumberOfPages() - 1, 0);
		if(! in_array($last, $range)) {
			$shy = $this->getPaginationShyButton($component->getNumberOfPages() - 1, $component);
			$tpl->setVariable('LAST', $default_renderer->render($shy));
		}
	}

	/**
	 * look into current $_GET params and append left-overs that are
	 * not controlled by this component.
	 *
	 * @param string $url
	 * @return string
	 */
	protected function appendCurrentGetParamters($url) {
		$query = html_entity_decode(parse_url($url, PHP_URL_QUERY));
		parse_str($query, $params);
		foreach ($_GET as $key => $value) {
			if(! array_key_exists($key, $params)) {
				$url .= '&' .$key .'=' .$value;
			}
		}
		return $url;
	}

	/**
	 * @inheritdoc
	 */
	public function registerResources(\ILIAS\UI\Implementation\Render\ResourceRegistry $registry) {
		parent::registerResources($registry);
		$registry->register('./src/UI/templates/js/ViewControl/sortation.js');
		$registry->register('./src/UI/templates/js/ViewControl/pagination.js');
	}


	protected function maybeRenderId(Component\Component $component, $tpl, $block, $template_var) {
		$id = $this->bindJavaScript($component);
		if ($id !== null) {
			$tpl->setCurrentBlock($block);
			$tpl->setVariable($template_var, $id);
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function getComponentInterfaceName() {
		return array(
			Component\ViewControl\Mode::class,
			Component\ViewControl\Section::class,
			Component\ViewControl\Sortation::class,
			Component\ViewControl\Pagination::class,
			Component\ViewControl\Quickfilter::class
		);

	}

}
