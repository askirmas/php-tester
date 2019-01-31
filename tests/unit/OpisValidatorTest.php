<?php
require_once 'configs/validation_classes.php';

class TestingOpisFilteringFilter implements Opis\JsonSchema\IFilter {
    public function validate($value, array $args): bool {
        switch($value){
            case 'http://google.com':
                return true;
            break;
            default:
                return false;
            break;
        }
    }
}

class OpisValidatorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $_validator = null;
    
    protected function _before()
    {
        $this->_validator = new Opis\JsonSchema\Validator();
    }

    protected function _after()
    {
    }

    // tests
    public function testValidatorObjectInstantiation()
    {
        $validator = new Opis\JsonSchema\Validator();
        $this->assertInstanceOf('Opis\JsonSchema\Validator', $validator);
    }

    public function testSchemaValidationMethodReturnsInstanceOfValidationResultClass()
    {
        $data = json_decode('{}');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{}');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertInstanceOf('Opis\JsonSchema\ValidationResult', $result);
    }

    public function testSchemaValidationMethodReturnsNotIsValidResult()
    {
        $data = json_decode('{ "name": [] }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testSchemaValidationMethodReturnsIsValidResult()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testAdditionalPropertiesForbiddenButPresent()
    {
        $data = json_decode('{ "name": "Andrii", "surname": "Shykov" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "additionalProperties": false
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    public function testAdditionalPropertiesForbiddenAndNotPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "additionalProperties": false
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testRequiredPropertiesNotPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "required": ["surname"]
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    public function testRequiredPropertiesPresent()
    {
        $data = json_decode('{ "name": "Andrii", "surname": "Shykov" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "required": ["surname"]
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testEnumPropertiesPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "enum": ["Andrii", "Alex"] }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testEnumPropertiesIsNotPresent()
    {
        $data = json_decode('{ "name": "Sasha" }');
        $schemaString = '{ "type": "object",
                            "properties": {
                                    "name": { "enum": ["Andrii", "Alex"] }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testFormatUriForValidUri()
    {
        $data = json_decode('{ "gateway": "http://google.com" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "gateway": { "type": "string", "format": "uri" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testFormatUriForNotValidUri()
    {
        $data = json_decode('{ "gateway": "~http://google.com" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "gateway": { "type": "string", "format": "uri" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testPropertyNamesForValidPropertyNames()
    {
        $data = json_decode('{ "gateway": "http://google.com" }');
        $schemaString = '{ "type": "object",
                           "propertyNames": {
                                "type": "string",
                                "minLength": 4
                           },
                               "properties": {
                                    "gateway": { "type": "string" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testPropertyNamesForInvalidPropertyNames()
    {
        $data = json_decode('{ "url": "http://google.com" }');
        $schemaString = '{ "type": "object",
                           "propertyNames": {
                                "type": "string",
                                "minLength": 4
                           },
                               "properties": {
                                    "gateway": { "type": "string" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    public function testPatternPropertiesForValidAndInvalidProperties()
    {
        $invalidData = json_decode('{ "url": "http://google.com", "amount": "hello" }');
        $validData = json_decode('{ "url": "http://google.com", "amount": 100500 }');
        $schemaString = '{ "type": "object",
                           "patternProperties": {
                                "^url": {
                                    "type": "string"
                                    },
                                "^amount": {
                                    "type": "integer"
                                    }
                                }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $resultForValidData = $this->_validator->schemaValidation($validData, $schema);
        $resultForInvalidData = $this->_validator->schemaValidation($invalidData, $schema);
        $this->assertTrue($resultForValidData->isValid(), 'Not expected result for valid data!');
        $this->assertFalse($resultForInvalidData->isValid(), 'Not expected result for invalid data!');
    }


    public function testRelativeJsonPointer()
    {
        $validData = json_decode('{ "url": "http://google.com", "amount": 100500, "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                           "properties": {
                                "url": {
                                    "type": "string"
                                },
                                "amount": {
                                    "type": "integer"
                                },
                                "name": {
                                    "$ref": "1/url"
                                }
                            }
                         }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $resultForValidData = $this->_validator->schemaValidation($validData, $schema);
        $this->assertTrue($resultForValidData->isValid(), 'Not expected result for valid data!');
    }

    public function testAbsoluteJsonPointer()
    {
        $validData = json_decode('{
                         "url": "http://google.com",
                         "amount": 100500,
                         "name": "Andrii"
                     }');
        $schemaString = '{
                           "$id": "http://example.com/path/to/user.json",
                           "type": "object",
                           "properties": {
                                "url": {
                                    "type": "string"
                                },
                                "amount": {
                                    "type": "integer"
                                },
                                "name": {
                                    "$ref": "#/some_definitions/name"
                                }
                            },
                            "some_definitions": {
                                "name": {
                                    "type": "string"
                                }
                            }
                         }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $resultForValidData = $this->_validator->schemaValidation($validData, $schema);
        $this->assertTrue($resultForValidData->isValid(), 'Not expected result for valid data!');
    }

    public function testValidatorFilterApplication()
    {
        $validData = json_decode('{
                         "url": "http://google.com",
                         "amount": 100500,
                         "name": "Andrii"
                     }');
        $invalidData = json_decode('{
                         "url": "http://google.co.uk",
                         "amount": 100500,
                         "name": "Andrii"
                     }');
        $schemaString = '{
                           "$id": "http://example.com/path/to/user.json",
                           "type": "object",
                           "properties": {
                                "url": {
                                    "type": "string",
                                    "$filters": { "$func": "testingOpisFiltering" }
                                },
                                "amount": {
                                    "type": "integer"
                                },
                                "name": {
                                    "type": "string"
                                }
                            }
                         }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $validator = new Opis\JsonSchema\Validator();
        $filters = new Opis\JsonSchema\FilterContainer();
        $filters->add('string', 'testingOpisFiltering', new TestingOpisFilteringFilter());
        $validator->setFilters($filters);
        $resultForValidData = $validator->schemaValidation($validData, $schema);
        $resultForInvalidData = $validator->schemaValidation($invalidData, $schema);
        $this->assertTrue($resultForValidData->isValid(), 'Not expected result for valid data!');
        $this->assertFalse($resultForInvalidData->isValid(), 'Not expected result for invalid data!');
    }

    protected function _writeObjectToLog($object, $logName = 'some_log.txt')
    {
        ob_start();
        echo "\nCurrent object:\n";
        echo json_encode($object, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
        file_put_contents($logName, ob_get_contents());
        ob_end_clean();
    }


}