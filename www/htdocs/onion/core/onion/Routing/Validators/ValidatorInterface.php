<?php
namespace Onion\Routing\Validators;

use Onion\Routing\RouteItem;
use Onion\Http\Request;

interface ValidatorInterface {
	public function matches(RouteItem $route, Request $request);
}