<?php

/*
 * libasynql_v3
 *
 * Copyright (C) 2018 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace poggit\libasynql\generic;

use InvalidArgumentException;
use RuntimeException;
use function array_map;
use function assert;
use function bin2hex;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function random_bytes;

class MysqlStatementImpl extends GenericStatementImpl{
	public function getDialect() : string{
		return "mysql";
	}

	protected function formatVariable(GenericVariable $variable, $value) : ?string{
		if($variable->isList()){
			assert(is_array($value));
			if(empty($value)){
				if(!$variable->canBeEmpty()){
					throw new InvalidArgumentException("Cannot pass an empty array for :{$variable->getName()}");
				}

				return "('" . bin2hex(random_bytes(20)) . ")";
			}

			$unlist = $variable->unlist();
			return "(" . implode(",", array_map(function($value) use ($unlist){
					return $this->formatVariable($unlist, $value);
				}, $value));
		}

		switch($variable->getType()){
			case GenericVariable::TYPE_BOOL:
				assert(is_bool($value));
				return $value ? "1" : "0";

			case GenericVariable::TYPE_INT:
				assert(is_int($value));
				return (string) $value;

			case GenericVariable::TYPE_FLOAT:
				assert(is_int($value) || is_float($value));
				return (string) $value;

			case GenericVariable::TYPE_STRING:
				assert(is_string($value));
				return null;

			case GenericVariable::TYPE_TIMESTAMP:
				assert(is_int($value) || is_float($value));
				if($value === GenericVariable::TIME_NOW){
					return "CURRENT_TIMESTAMP";
				}
				return "FROM_UNIXTIME($value)";
		}

		throw new RuntimeException("Unsupported variable type");
	}
}