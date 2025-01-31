<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\DialogTable;

class SelectTable extends Field
{
    use PlainInput;

    protected static $js = [
        '@select-table',
    ];

    /**
     * @var DialogTable
     */
    protected $dialog;

    protected $style = 'primary';

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        $this->dialog = DialogTable::make($this->label);
    }

    /**
     * 设置弹窗标题.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title($title)
    {
        $this->dialog->title($title);

        return $this;
    }

    /**
     * 设置弹窗宽度.
     *
     * @example
     *    $this->width('500px');
     *    $this->width('50%');
     *
     * @param string $width
     *
     * @return $this
     */
    public function dialogWidth(string $width)
    {
        $this->dialog->width($width);

        return $this;
    }

    /**
     * 设置表格异步渲染实例.
     *
     * @param LazyRenderable $renderable
     *
     * @return $this
     */
    public function from(LazyRenderable $renderable)
    {
        $this->dialog->from($renderable);

        return $this;
    }

    /**
     * 转化为数组格式保存.
     *
     * @param mixed $value
     *
     * @return array|mixed
     */
    public function prepareInputValue($value)
    {
        return Helper::array($value, true);
    }

    protected function formatOptions()
    {
        $value = Helper::array(old($this->column, $this->value()));

        if ($this->options instanceof \Closure) {
            $this->options = $this->options->call($this->values(), $value, $this);
        }

        $values = [];

        foreach (Helper::array($this->options) as $id => $label) {
            foreach ($value as $v) {
                if ($v == $id && $v !== null) {
                    $values[] = ['id' => $v, 'label' => $label];
                }
            }
        }

        $this->options = json_encode($values);
    }

    protected function addScript()
    {
        $this->script .= <<<JS
Dcat.grid.SelectTable({
    dialog: replaceNestedFormIndex('#{$this->dialog->id()}'),
    container: replaceNestedFormIndex('#{$this->getAttribute('id')}'),
    input: replaceNestedFormIndex('#hidden-{$this->id}'),
    values: {$this->options},
});
JS;
    }

    protected function setUpTable()
    {
        $this->dialog
            ->id($this->getElementId())
            ->runScript(false)
            ->footer($this->renderFooter())
            ->button($this->renderButton());
    }

    public function render()
    {
        $this->setUpTable();
        $this->formatOptions();

        $name = $this->getElementName();

        $this->prepend('<i class="feather icon-arrow-up"></i>')
            ->defaultAttribute('class', 'form-control '.$this->getElementClassString())
            ->defaultAttribute('type', 'text')
            ->defaultAttribute('name', $name)
            ->defaultAttribute('id', 'container-'.$this->getElementId());

        $this->addVariables([
            'prepend'     => $this->prepend,
            'append'      => $this->append,
            'style'       => $this->style,
            'dialog'      => $this->dialog->render(),
            'placeholder' => $this->placeholder(),
        ]);

        $this->script = $this->dialog->getScript();

        $this->addScript();

        return parent::render();
    }

    protected function renderButton()
    {
        return <<<HTML
<div class="btn btn-{$this->style}">
    &nbsp;<i class="feather icon-arrow-up"></i>&nbsp;
</div>
HTML;
    }

    /**
     * 弹窗底部内容构建.
     *
     * @return string
     */
    protected function renderFooter()
    {
        $submit = trans('admin.submit');
        $cancel = trans('admin.cancel');

        return <<<HTML
<button class="btn btn-primary btn-sm submit-btn" style="color: #fff">&nbsp;{$submit}&nbsp;</button>&nbsp;
<button  class="btn btn-white btn-sm cancel-btn">&nbsp;{$cancel}&nbsp;</button>
HTML;
    }
}
