<?php
namespace Onion\Routing\Validators;

use Onion\Routing\RouteItem;
use Onion\Http\Request;

class MethodValidator implements ValidatorInterface {
	public function matches(RouteItem $route, Request $request) {
		return in_array($request->getMethod(), $route->getMethods());
	}
}