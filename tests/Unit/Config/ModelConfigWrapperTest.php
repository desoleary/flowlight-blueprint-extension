<?php

namespace Tests\Unit\Config;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Fields\Field;
use Flowlight\Generator\Fields\FieldCollection;

beforeEach(function () {
    $this->wrapper = new class('User', ['fields' => ['name' => ['type' => 'string', 'required' => true], 'age' => ['type' => 'int', 'required' => false]], 'dto' => ['namespace' => 'Custom\\Namespace', 'className' => 'CustomDto', 'extends' => 'BaseDto'], 'table' => 'custom_users'], 'dto') extends ModelConfigWrapper
    {
        protected function getDefaultNamespace(): string
        {
            return 'App\\Domain\\Users\\Data';
        }

        protected function getDefaultClassName(): string
        {
            return 'UserData';
        }

        protected function getDefaultExtendedClassName(): ?string
        {
            return 'Flowlight\\BaseData';
        }
    };
});

describe('getModelName', function () {
    it('returns the model name', function () {
        expect($this->wrapper->getModelName())->toBe('User');
    });
});

describe('getType', function () {
    it('returns the generator type', function () {
        expect($this->wrapper->getType())->toBe('dto');
    });
});

describe('getDefinition', function () {
    it('returns the raw definition array', function () {
        $def = $this->wrapper->getDefinition();
        expect($def)->toBeArray()->toHaveKey('fields')->toHaveKey('dto');
    });
});

describe('getFields', function () {
    it('initializes fields as FieldConfig instances', function () {
        $fields = $this->wrapper->getFields();
        expect($fields)->toBeInstanceOf(FieldCollection::class);
        expect($fields->get('name'))->toBeInstanceOf(Field::class);
        expect($fields->get('age'))->toBeInstanceOf(Field::class);
    });

    it('when no fields defined it returns an empty FieldCollection', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };

        $fields = $wrapper->getFields();
        expect($fields)->toBeInstanceOf(\Flowlight\Generator\Fields\FieldCollection::class);
        expect($fields->count())->toBe(0);
    });
});

describe('getField', function () {
    it('returns a single field config by name', function () {
        $field = $this->wrapper->getField('name');
        expect($field)->toBeInstanceOf(Field::class);
        expect($field->getName())->toBe('name');
    });

    it('returns null for missing field', function () {
        expect($this->wrapper->getField('missing'))->toBeNull();
    });
});

describe('shouldGenerate', function () {
    it('returns true when type section is true', function () {
        $wrapper = new class('User', ['dto' => true], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect($wrapper->shouldGenerate())->toBeTrue();
    });

    it('returns true when type section is non-empty array', function () {
        $wrapper = new class('User', ['dto' => ['namespace' => 'X']], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect($wrapper->shouldGenerate())->toBeTrue();
    });

    it('returns false otherwise', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect($wrapper->shouldGenerate())->toBeFalse();
    });
});

describe('getNamespace', function () {
    it('returns namespace from config when present', function () {
        expect($this->wrapper->getNamespace())->toBe('Custom\\Namespace');
    });

    it('falls back to default namespace when not configured', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'Default\\NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'UserData';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect($wrapper->getNamespace())->toBe('Default\\NS');
    });

    it('throws when fallback namespace is blank', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return '';
            }

            protected function getDefaultClassName(): string
            {
                return 'UserData';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect(fn () => $wrapper->getNamespace())->toThrow(\LogicException::class);
    });
});

describe('getClassName', function () {
    it('returns className from config when present', function () {
        expect($this->wrapper->getClassName())->toBe('CustomDto');
    });

    it('falls back to default className when not configured', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'Fallback';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect($wrapper->getClassName())->toBe('Fallback');
    });

    it('throws when fallback className is blank', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return '';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };
        expect(fn () => $wrapper->getClassName())->toThrow(\LogicException::class);
    });
});

describe('getExtendedClassName', function () {
    it('returns extended class from config when present', function () {
        expect($this->wrapper->getExtendedClassName())->toBe('BaseDto');
    });

    it('falls back to default extended class when not configured', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'DefaultBase';
            }
        };
        expect($wrapper->getExtendedClassName())->toBe('DefaultBase');
    });

    it('throws when fallback extended class is blank', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return '';
            }
        };
        expect(fn () => $wrapper->getExtendedClassName())->toThrow(\LogicException::class);
    });

    it('throws when fallback extended class is null', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return null;
            }
        };
        expect(fn () => $wrapper->getExtendedClassName())->toThrow(\LogicException::class);
    });
});

describe('getTableName', function () {
    it('returns table name from config when provided', function () {
        expect($this->wrapper->getTableName())->toBe('custom_users');
    });

    it('returns snake plural of model name by default', function () {
        $wrapper = new class('Person', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return null;
            }
        };
        expect(fn () => $wrapper->getExtendedClassName())->toThrow(\LogicException::class);
    });

    it('throws when configured table name is blank', function () {
        $wrapper = new class('User', ['table' => ''], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return null;
            }
        };
        expect(fn () => $wrapper->getTableName())->toThrow(\LogicException::class);
    });

    it('throws a LogicException if the table key is not set at all', function () {
        $wrapper = new class('User', [], 'dto') extends \Flowlight\Generator\Config\ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };

        try {
            $wrapper->getTableName();
            $this->fail('Expected LogicException was not thrown');
        } catch (\LogicException $e) {
            expect($e->getMessage())->toContain('table name must be provided');
        }
    });
});
