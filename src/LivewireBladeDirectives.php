<?php

namespace Livewire;

use Illuminate\Support\Str;

class LivewireBladeDirectives
{
    public static function this()
    {
        return "window.livewire.find('{{ \$_instance->id }}')";
    }

    public static function entangle($expression)
    {
        return <<<EOT
<?php if ((object) ({$expression}) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('{{ \$_instance->id }}').entangle('{{ {$expression}->value() }}'){{ {$expression}->hasModifier('defer') ? '.defer' : '' }}<?php else : ?>window.Livewire.find('{{ \$_instance->id }}').entangle('{{ {$expression} }}')<?php endif; ?>
EOT;
    }

    public static function js($expression)
    {
        return <<<EOT
<?php
    if (is_object({$expression}) || is_array({$expression})) {
        echo "JSON.parse(atob('".base64_encode(json_encode({$expression}))."'))";
    } elseif (is_string({$expression})) {
        echo "'".str_replace("'", "\'", {$expression})."'";
    } else {
        echo json_encode({$expression});
    }
?>
EOT;
    }

    public static function livewireStyles($expression)
    {
        return '{!! \Livewire\Livewire::styles('.$expression.') !!}';
    }

    public static function livewireScripts($expression)
    {
        return '{!! \Livewire\Livewire::scripts('.$expression.') !!}';
    }

    public static function livewire($expression)
    {
        $cachedKey = "'" . Str::random(7) . "'";

        // If we are inside a Livewire component, we know we're rendering a child.
        // Therefore, we must create a more deterministic view cache key so that
        // Livewire children are properly tracked across load balancers.
        if (LivewireManager::$currentCompilingViewPath !== null) {
            // $cachedKey = '[hash of Blade view path]-[current @livewire directive count]'
            $cachedKey = "'l" . crc32(LivewireManager::$currentCompilingViewPath) . "-" . LivewireManager::$currentCompilingChildCounter . "'";

            // We'll increment count, so each cache key inside a compiled view is unique.
            LivewireManager::$currentCompilingChildCounter++;
        } 

        $pattern = "/,\s*?key\(([\s\S]*)\)/"; //everything between ",key(" and ")"
        $expression = preg_replace_callback($pattern, function ($match) use (&$cachedKey) {
            $cachedKey = trim($match[1]) ?: $cachedKey;
            return "";
        }, $expression);

        return <<<EOT
<?php
if (! isset(\$_instance)) {
    \$html = \Livewire\Livewire::mount({$expression})->html();
} elseif (\$_instance->childHasBeenRendered($cachedKey)) {
    \$componentId = \$_instance->getRenderedChildComponentId($cachedKey);
    \$componentTag = \$_instance->getRenderedChildComponentTagName($cachedKey);
    \$html = \Livewire\Livewire::dummyMount(\$componentId, \$componentTag);
    \$_instance->preserveRenderedChild($cachedKey);
} else {
    \$response = \Livewire\Livewire::mount({$expression});
    \$html = \$response->html();
    \$_instance->logRenderedChild($cachedKey, \$response->id(), \Livewire\Livewire::getRootElementTagName(\$html));
}
echo \$html;
?>
EOT;
    }

    public static function stack($name, $default = "''") {
        $expression = rtrim("{$name}, {$default}", ', ');

        return "
            <?php if (in_array(${name}, \Livewire\LivewireManager::\$livewirefiedBladeStacks)) : ?>
            <template livewire-stack=\"<?php echo {$name}; ?>\"></template>
            <?php endif; ?>
            <?php echo \$__env->yieldPushContent($expression); ?>
            <?php if (in_array(${name}, \Livewire\LivewireManager::\$livewirefiedBladeStacks)) : ?>
            <template livewire-end-stack=\"<?php echo {$name}; ?>\"></template>
            <?php endif; ?>
        ";
    }

    public static function once($id = null) {
        $id = $id ?: "'".(string) Str::uuid()."'";

        return "<?php
            if (isset(\$_instance)) \$__stack_once = true;

            if (! \$__env->hasRenderedOnce({$id})): \$__env->markAsRenderedOnce({$id});
        ?>";
    }

    public static function endonce() {
        return "<?php
            endif;

            if (isset(\$_instance) && isset(\$__stack_once)) unset(\$__stack_once);
        ?>";
    }

    public static function push($name, $content = "''") {
        $randomKey = Str::random(9);

        return "<?php
            \$__is_livewirefied = str_starts_with(${name}, 'livewire:');
            \$__stack_name = str_replace('livewire:', '', {$name});

            if (isset(\$_instance) && \$__is_livewirefied) {
                \Livewire\LivewireManager::\$livewirefiedBladeStacks[] = \$__stack_name;

                \$__stack_item_key = isset(\$__stack_once) ? crc32(\$__path) : '{$randomKey}';

                \$__env->startPush(\$__stack_name, {$content});

                ob_start();

                echo '<template livewire-stack-key=\"'.\$__stack_item_key.'\"></template>';
            } else {
                \$__env->startPush(\$__stack_name, {$content});
            }

            unset(\$__is_livewirefied);
        ?>";
    }

    public static function pushOnce($name, $id = null) {
        return static::once($id).static::push($name);
    }

    public static function prepend($name, $content = "''") {
        $randomKey = Str::random(9);
        $expression = rtrim("{$name}, {$content}", ', ');

        return "<?php
            \$__is_livewirefied = str_starts_with(${name}, 'livewire:');
            \$__stack_name = str_replace('livewire:', '', {$name});

            if (isset(\$_instance) && \$__is_livewirefied) {
                \Livewire\LivewireManager::\$livewirefiedBladeStacks[] = \$__stack_name;
                
                \$__stack_item_key = isset(\$__stack_once) ? crc32(\$__path) : '{$randomKey}';

                \$__env->startPrepend(\$__stack_name, {$content});

                ob_start();

                echo '<template livewire-stack-key=\"'.\$__stack_item_key.'\"></template>';
            } else {
                \$__env->startPrepend(\$__stack_name, {$content});
            }

            unset(\$__is_livewirefied);
        ?>";
    }
    public static function prependOnce($name, $id = null) {
        return static::once($id).static::prepend($name);
    }

    public static function endpush() {
        return "<?php
            if (isset(\$_instance) && isset(\$__stack_item_key)) {
                \$__contents = ob_get_clean();

                \$_instance->addToStack(\$__stack_name, 'push', \$__contents, \$__stack_item_key);

                echo \$__contents;
                unset(\$__contents);

                unset(\$__stack_item_key);
                unset(\$__stack_name);

                \$__env->stopPush();
            } else {
                \$__env->stopPush();
            }
        ?>";
    }

    public static function endpushOnce() {
        return static::endpush().static::endonce();
    }

    public static function endprepend() {
        return "<?php
            if (isset(\$_instance) && isset(\$__stack_item_key)) {
                \$__contents = ob_get_clean();

                \$_instance->addToStack(\$__stack_name, 'prepend', \$__contents, \$__stack_item_key);

                echo \$__contents;
                unset(\$__contents);

                unset(\$__stack_item_key);
                unset(\$__stack_name);

                \$__env->stopPrepend();
            } else {
                \$__env->stopPrepend();
            }
        ?>";
    }

    public static function endprependOnce() {
        return static::endprepend().static::endonce();
    }

    public static function stripQuotes(string $value)
    {
        return Str::startsWith($value, ['"', '\''])
                    ? substr($value, 1, -1)
                    : $value;
    }
}
