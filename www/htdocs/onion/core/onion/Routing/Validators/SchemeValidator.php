<?php
namespace Onion\Routing\Validators;

use Onion\Routing\RouteItem;
use Onion\Http\Request;

class SchemeValidator implements ValidatorInterface {
	public function matches(RouteItem $route, Request $request) {
		$action = $route->getAction();

		if (isset($action['http'])) {
			return !$request->isSecure();
		} else if(isset($action['https'])) {
			return $request->isSecure();
		}

		return true;
	}
}