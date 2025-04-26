<?php
/**
 * Base View Class
 * 
 * Provides template rendering and view helper functionality:
 * - Template rendering
 * - Layout management
 * - Asset handling
 * - Form helpers
 * - HTML helpers
 */
class View {
    protected $layout = 'main';
    protected $view;
    protected $data = [];
    protected $sections = [];
    protected $currentSection;
    protected $scripts = [];
    protected $styles = [];
    
    /**
     * Constructor
     */
    public function __construct($view = null, $data = []) {
        $this->view = $view;
        $this->data = array_merge([
            'title' => '',
            'breadcrumbs' => [],
            'flash' => $this->getFlashMessages()
        ], $data);
    }
    
    /**
     * Set layout
     */
    public function setLayout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Set view
     */
    public function setView($view) {
        $this->view = $view;
        return $this;
    }
    
    /**
     * Set data
     */
    public function setData($data) {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
    
    /**
     * Add script
     */
    public function addScript($script, $position = 'footer') {
        $this->scripts[$position][] = $script;
        return $this;
    }
    
    /**
     * Add style
     */
    public function addStyle($style) {
        $this->styles[] = $style;
        return $this;
    }
    
    /**
     * Render view
     */
    public function render() {
        // Extract data for view
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = APP_ROOT . '/views/' . $this->view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$this->view}");
        }
        
        include $viewFile;
        
        // Get view content
        $content = ob_get_clean();
        
        // If no layout, return content
        if (!$this->layout) {
            return $content;
        }
        
        // Include layout
        $layoutFile = APP_ROOT . '/views/layouts/' . $this->layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout file not found: {$this->layout}");
        }
        
        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }
    
    /**
     * Start section
     */
    public function section($name) {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * End section
     */
    public function endSection() {
        if (!$this->currentSection) {
            return;
        }
        
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }
    
    /**
     * Get section content
     */
    public function getSection($name) {
        return $this->sections[$name] ?? '';
    }
    
    /**
     * Include partial view
     */
    public function partial($view, $data = []) {
        $data = array_merge($this->data, $data);
        extract($data);
        
        $partialFile = APP_ROOT . '/views/partials/' . $view . '.php';
        if (!file_exists($partialFile)) {
            throw new Exception("Partial view not found: {$view}");
        }
        
        include $partialFile;
    }
    
    /**
     * Get scripts
     */
    public function getScripts($position = 'footer') {
        return $this->scripts[$position] ?? [];
    }
    
    /**
     * Get styles
     */
    public function getStyles() {
        return $this->styles;
    }
    
    /**
     * Get flash messages
     */
    protected function getFlashMessages() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
    
    /**
     * HTML Helpers
     */
    
    /**
     * Create HTML tag
     */
    public function tag($name, $content = '', $attributes = []) {
        $html = "<$name";
        
        foreach ($attributes as $key => $value) {
            $html .= " $key=\"" . htmlspecialchars($value) . "\"";
        }
        
        if ($content === false) {
            return "$html />";
        }
        
        return "$html>$content</$name>";
    }
    
    /**
     * Create link
     */
    public function link($text, $url = '#', $attributes = []) {
        $attributes['href'] = $url;
        return $this->tag('a', htmlspecialchars($text), $attributes);
    }
    
    /**
     * Create image
     */
    public function image($src, $alt = '', $attributes = []) {
        $attributes['src'] = $src;
        $attributes['alt'] = $alt;
        return $this->tag('img', false, $attributes);
    }
    
    /**
     * Form Helpers
     */
    
    /**
     * Create form open tag
     */
    public function formOpen($action = '', $method = 'POST', $attributes = []) {
        $attributes['action'] = $action;
        $attributes['method'] = $method;
        
        $html = $this->tag('form', '', $attributes);
        
        if (strtoupper($method) === 'POST') {
            $html .= $this->tag('input', false, [
                'type' => 'hidden',
                'name' => 'csrf_token',
                'value' => $_SESSION['csrf_token'] ?? ''
            ]);
        }
        
        return $html;
    }
    
    /**
     * Create form close tag
     */
    public function formClose() {
        return '</form>';
    }
    
    /**
     * Create input field
     */
    public function input($type, $name, $value = '', $attributes = []) {
        $attributes['type'] = $type;
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        return $this->tag('input', false, $attributes);
    }
    
    /**
     * Create textarea
     */
    public function textarea($name, $value = '', $attributes = []) {
        $attributes['name'] = $name;
        return $this->tag('textarea', htmlspecialchars($value), $attributes);
    }
    
    /**
     * Create select field
     */
    public function select($name, $options = [], $selected = null, $attributes = []) {
        $attributes['name'] = $name;
        
        $html = '';
        foreach ($options as $value => $label) {
            $optionAttributes = ['value' => $value];
            
            if ($value == $selected) {
                $optionAttributes['selected'] = 'selected';
            }
            
            $html .= $this->tag('option', htmlspecialchars($label), $optionAttributes);
        }
        
        return $this->tag('select', $html, $attributes);
    }
    
    /**
     * Create checkbox
     */
    public function checkbox($name, $value = '1', $checked = false, $attributes = []) {
        $attributes['type'] = 'checkbox';
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        
        if ($checked) {
            $attributes['checked'] = 'checked';
        }
        
        return $this->tag('input', false, $attributes);
    }
    
    /**
     * Create radio button
     */
    public function radio($name, $value, $checked = false, $attributes = []) {
        $attributes['type'] = 'radio';
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        
        if ($checked) {
            $attributes['checked'] = 'checked';
        }
        
        return $this->tag('input', false, $attributes);
    }
    
    /**
     * Create submit button
     */
    public function submit($text = 'Submit', $attributes = []) {
        $attributes['type'] = 'submit';
        return $this->tag('button', htmlspecialchars($text), $attributes);
    }
    
    /**
     * Create button
     */
    public function button($text, $attributes = []) {
        return $this->tag('button', htmlspecialchars($text), $attributes);
    }
    
    /**
     * Create label
     */
    public function label($text, $for = '', $attributes = []) {
        if ($for) {
            $attributes['for'] = $for;
        }
        return $this->tag('label', htmlspecialchars($text), $attributes);
    }
}
