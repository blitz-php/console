<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\Console\Traits\InteractsWithParameters;

describe('InteractsWithParameters', function () {
    beforeEach(function () {
        $this->params = new class () {
            use InteractsWithParameters;

            public function exposeSetParameters(array $arguments, array $options)
            {
                $this->setParameters($arguments, $options);
            }
        };
    });

    describe('setParameters', function () {
        it('stores arguments and options', function () {
            $arguments = ['name' => 'John', 'age' => 30];
            $options   = ['verbose' => true, 'force' => false];

            $this->params->exposeSetParameters($arguments, $options);

            expect($this->params->arguments())->toBe($arguments);
            expect($this->params->options())->toBe($options);
        });
    });

    describe('argument methods', function () {
        beforeEach(function () {
            $this->params->exposeSetParameters(
                ['id' => 123, 'name' => 'Product'],
                ['verbose' => true],
            );
        });

        it('retrieves argument value', function () {
            expect($this->params->argument('id'))->toBe(123);
            expect($this->params->argument('name'))->toBe('Product');
        });

        it('returns default for missing argument', function () {
            expect($this->params->argument('missing', 'default'))->toBe('default');
        });

        it('checks if argument exists', function () {
            expect($this->params->hasArgument('id'))->toBe(true);
            expect($this->params->hasArgument('missing'))->toBe(false);
        });

        it('returns all arguments', function () {
            $args = $this->params->arguments();

            expect($args)->toContainKeys(['id', 'name']);
            expect($args['id'])->toBe(123);
        });
    });

    describe('option methods', function () {
        beforeEach(function () {
            $this->params->exposeSetParameters(
                ['id' => 123],
                ['verbose' => true, 'env' => 'prod', 'debug' => false],
            );
        });

        it('retrieves option value', function () {
            expect($this->params->option('verbose'))->toBe(true);
            expect($this->params->option('env'))->toBe('prod');
            expect($this->params->option('debug'))->toBe(false);
        });

        it('returns default for missing option', function () {
            expect($this->params->option('missing', 'default'))->toBe('default');
        });

        it('checks if option exists', function () {
            expect($this->params->hasOption('verbose'))->toBe(true);
            expect($this->params->hasOption('missing'))->toBe(false);
        });

        it('returns all options', function () {
            $options = $this->params->options();

            expect($options)->toContainKeys(['verbose', 'env', 'debug']);
            expect($options['verbose'])->toBe(true);
            expect($options['env'])->toBe('prod');
        });
    });

    describe('parameter method', function () {
        beforeEach(function () {
            $this->params->exposeSetParameters(
                ['action' => 'create'],
                ['force' => true, 'id' => 999],
            );
        });

        it('retrieves argument or option by name', function () {
            expect($this->params->parameter('action'))->toBe('create');
            expect($this->params->parameter('force'))->toBe(true);
        });

        it('prefers arguments over options when names conflict', function () {
            $params = (clone $this->params);
            $params->exposeSetParameters(
                ['id' => 999],
                ['id' => 123],
            );
            // 'id' existe comme argument et option, argument doit être retourné
            expect($params->parameter('id'))->toBe(123); // argument, pas 999
        });

        it('returns default for missing parameter', function () {
            expect($this->params->parameter('missing', 'default'))->toBe('default');
        });
    });
});
