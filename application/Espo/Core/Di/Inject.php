<?php

namespace Espo\Core\Di;

use Attribute;

/**
 * Attribute for property injection.
 * Can be used on properties to inject services from the container.
 * 
 * If a service name is not specified, it will be derived from the property type.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
	/**
	 * @param ?string $name The service name. If null, the name will be derived from the property or parameter type.
	 */
	public function __construct(
		public readonly ?string $name = null
	) {
	}
}
