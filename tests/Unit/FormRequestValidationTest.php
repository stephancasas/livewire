<?php

namespace Tests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;
use Livewire\Livewire;
use function view;

class FormRequestValidationTest extends TestCase
{
    /** @test */
    public function standard_model_property()
    {
        Livewire::test(ComponentWithFormRequestForValidationRules::class, [
            'foo' => '',
        ])
            ->call('save')
            ->assertHasErrors('foo')
            ->set('foo', 'baz')
            ->call('save')
            ->assertHasNoErrors();
    }
}

class FormRequestForValidationTest extends FormRequest
{
    public function rules()
    {
        return ['foo' => 'required'];
    }
}

class ComponentWithFormRequestForValidationRules extends Component
{
    public $foo = '';

    protected $rules = FormRequestForValidationTest::class;

    public function save()
    {
        $this->validate();
    }

    public function render()
    {
        return view('dump-errors');
    }
}
