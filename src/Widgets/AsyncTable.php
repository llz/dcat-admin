<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Grid\LazyRenderable;
use Illuminate\Support\Str;

class AsyncTable extends Widget
{
    public static $js = [
        '@grid-extension',
    ];

    /**
     * @var LazyRenderable
     */
    protected $renderable;

    /**
     * 设置是否自动加载.
     *
     * @var bool
     */
    protected $load = true;

    /**
     * 设置是否启用表格简化模式.
     *
     * @var bool
     */
    protected $simple;

    /**
     * AsyncTable constructor.
     *
     * @param LazyRenderable $renderable
     * @param bool $load
     */
    public function __construct(LazyRenderable $renderable, bool $load = true)
    {
        $this->from($renderable);
        $this->load($load);

        $this->class('async-table');
        $this->id('async-table-'.Str::random(8));
    }

    /**
     * 设置异步表格实例.
     *
     * @param LazyRenderable|null $renderable
     *
     * @return $this
     */
    public function from(?LazyRenderable $renderable)
    {
        if (! $renderable) {
            return $this;
        }

        $this->renderable = $renderable;

        return $this;
    }

    /**
     * 设置是否自动加载.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function load(bool $value)
    {
        $this->load = $value;

        return $this;
    }

    /**
     * 设置是否启用表格简化模式.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function simple(bool $value = true)
    {
        $this->simple = $value;

        if ($value) {
            $this->class('simple-grid', true);
        }

        return $this;
    }

    /**
     * 监听异步渲染完成事件.
     *
     * @param string $script
     *
     * @return $this
     */
    public function onLoad(string $script)
    {
        $this->script .= "$('{$this->getElementSelector()}').on('table:loaded', function (event) { {$script} });";

        return $this;
    }

    protected function addScript()
    {
        $this->script = <<<JS
Dcat.grid.AsyncTable({container: '{$this->getElementSelector()}'});
JS;

        if ($this->load) {
            $this->script .= $this->getLoadScript();
        }
    }

    /**
     * @return string
     */
    public function getElementSelector()
    {
        return '#'.$this->getHtmlAttribute('id');
    }

    /**
     * @return string
     */
    public function getLoadScript()
    {
        return <<<JS
$('{$this->getElementSelector()}').trigger('table:load');
JS;
    }

    public function render()
    {
        if ($this->simple !== null) {
            $this->renderable->simple();
        }

        $this->addScript();

        return parent::render();
    }

    public function html()
    {
        $this->setHtmlAttribute([
            'data-url' => $this->renderable->getUrl(),
        ]);

        return <<<HTML
<div {$this->formatHtmlAttributes()} style="min-height: 200px"></div>        
HTML;
    }
}
