<?php
/**
 * Base Form Class
 * 
 * Provides form handling functionality:
 * - Form validation
 * - Data handling
 * - HTML generation
 * - CSRF protection
 */
class Form {
    protected $data = [];
    protected $files = [];
    protected $errors = [];
    protected $rules = [];
    protected $labels = [];
    protected $attributes = [];
    protected $validated = false;
    
    /**
     * Constructor
     */
    public function __construct($data = [], $files = []) {
        $this->data = $data;
        $this->files = $files;
    }
    
    /**
     * Set validation rules
     */
    public function setRules($rules) {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Set field labels
     */
    public function setLabels($labels) {
        $this->labels = $labels;
        return $this;
    }
    
    /**
     * Set field attributes
     */
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * Get field label
     */
    public function getLabel($field) {
        return $this->labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }
    
    /**
     * Get field attributes
     */
    public function getAttributes($field) {
        return $this->attributes[$field] ?? [];
    }
    
    /**
     * Validate form
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    $rule = ['type' => $rule];
                }
                
                $type = $rule['type'];
                $params = $rule['params'] ?? [];
                
                $method = 'validate' . ucfirst($type);
                if (method_exists($this, $method)) {
                    if (!$this->$method($field, $value, $params)) {
                        break;
                    }
                }
            }
        }
        
        $this->validated = true;
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get field error
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Get validated data
     */
    public function getData() {
        if (!$this->validated) {
            throw new Exception('Form has not been validated');
        }
        
        $data = [];
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $data[$field] = $this->data[$field];
            }
        }
        
        return $data;
    }
    
    /**
     * Get field value
     */
    public function getValue($field) {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Get file
     */
    public function getFile($field) {
        return $this->files[$field] ?? null;
    }
    
    /**
     * Add error
     */
    protected function addError($field, $message) {
        $this->errors[$field] = $message;
        return false;
    }
    
    /**
     * Validation Rules
     */
    
    /**
     * Required validation
     */
    protected function validateRequired($field, $value, $params) {
        if ($value === null || $value === '') {
            return $this->addError($field, $this->getLabel($field) . ' is required');
        }
        return true;
    }
    
    /**
     * Email validation
     */
    protected function validateEmail($field, $value, $params) {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->addError($field, $this->getLabel($field) . ' must be a valid email address');
        }
        return true;
    }
    
    /**
     * Numeric validation
     */
    protected function validateNumeric($field, $value, $params) {
        if ($value && !is_numeric($value)) {
            return $this->addError($field, $this->getLabel($field) . ' must be a number');
        }
        return true;
    }
    
    /**
     * Integer validation
     */
    protected function validateInteger($field, $value, $params) {
        if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
            return $this->addError($field, $this->getLabel($field) . ' must be an integer');
        }
        return true;
    }
    
    /**
     * Min length validation
     */
    protected function validateMinLength($field, $value, $params) {
        $min = $params[0] ?? null;
        if ($value && $min && strlen($value) < $min) {
            return $this->addError($field, $this->getLabel($field) . " must be at least {$min} characters");
        }
        return true;
    }
    
    /**
     * Max length validation
     */
    protected function validateMaxLength($field, $value, $params) {
        $max = $params[0] ?? null;
        if ($value && $max && strlen($value) > $max) {
            return $this->addError($field, $this->getLabel($field) . " must not exceed {$max} characters");
        }
        return true;
    }
    
    /**
     * Min value validation
     */
    protected function validateMin($field, $value, $params) {
        $min = $params[0] ?? null;
        if ($value && $min && $value < $min) {
            return $this->addError($field, $this->getLabel($field) . " must be at least {$min}");
        }
        return true;
    }
    
    /**
     * Max value validation
     */
    protected function validateMax($field, $value, $params) {
        $max = $params[0] ?? null;
        if ($value && $max && $value > $max) {
            return $this->addError($field, $this->getLabel($field) . " must not exceed {$max}");
        }
        return true;
    }
    
    /**
     * Pattern validation
     */
    protected function validatePattern($field, $value, $params) {
        $pattern = $params[0] ?? null;
        $message = $params[1] ?? null;
        if ($value && $pattern && !preg_match($pattern, $value)) {
            return $this->addError($field, $message ?? $this->getLabel($field) . ' is invalid');
        }
        return true;
    }
    
    /**
     * In array validation
     */
    protected function validateIn($field, $value, $params) {
        $values = $params[0] ?? [];
        if ($value && !in_array($value, $values)) {
            return $this->addError($field, $this->getLabel($field) . ' is invalid');
        }
        return true;
    }
    
    /**
     * Unique validation
     */
    protected function validateUnique($field, $value, $params) {
        if (!$value) {
            return true;
        }
        
        $table = $params[0] ?? null;
        $except = $params[1] ?? null;
        
        if (!$table) {
            throw new Exception('Table name is required for unique validation');
        }
        
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$field} = ?";
        $sqlParams = [$value];
        
        if ($except) {
            $sql .= " AND id != ?";
            $sqlParams[] = $except;
        }
        
        $result = $db->fetchOne($sql, $sqlParams);
        
        if ($result['count'] > 0) {
            return $this->addError($field, $this->getLabel($field) . ' is already taken');
        }
        
        return true;
    }
    
    /**
     * File validation
     */
    protected function validateFile($field, $value, $params) {
        $file = $this->getFile($field);
        if (!$file) {
            return true;
        }
        
        $maxSize = $params['size'] ?? null;
        $types = $params['types'] ?? null;
        
        if ($maxSize && $file['size'] > $maxSize) {
            return $this->addError($field, $this->getLabel($field) . ' is too large');
        }
        
        if ($types && !in_array($file['type'], $types)) {
            return $this->addError($field, $this->getLabel($field) . ' type is not allowed');
        }
        
        return true;
    }
    
    /**
     * Date validation
     */
    protected function validateDate($field, $value, $params) {
        if ($value && !strtotime($value)) {
            return $this->addError($field, $this->getLabel($field) . ' must be a valid date');
        }
        return true;
    }
    
    /**
     * Form HTML Generation
     */
    
    /**
     * Generate form open tag
     */
    public function open($action = '', $method = 'POST', $attributes = []) {
        $attributes['action'] = $action;
        $attributes['method'] = $method;
        
        if (!isset($attributes['enctype']) && !empty($this->files)) {
            $attributes['enctype'] = 'multipart/form-data';
        }
        
        $html = $this->tag('form', '', $attributes);
        
        if (strtoupper($method) === 'POST') {
            $html .= $this->tag('input', '', [
                'type' => 'hidden',
                'name' => 'csrf_token',
                'value' => $_SESSION['csrf_token'] ?? ''
            ]);
        }
        
        return $html;
    }
    
    /**
     * Generate form close tag
     */
    public function close() {
        return '</form>';
    }
    
    /**
     * Generate input field
     */
    public function input($field, $type = 'text') {
        $attributes = array_merge([
            'type' => $type,
            'name' => $field,
            'id' => $field,
            'value' => $this->getValue($field),
            'class' => 'form-control'
        ], $this->getAttributes($field));
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        return $this->tag('input', '', $attributes);
    }
    
    /**
     * Generate textarea
     */
    public function textarea($field) {
        $attributes = array_merge([
            'name' => $field,
            'id' => $field,
            'class' => 'form-control'
        ], $this->getAttributes($field));
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        return $this->tag('textarea', $this->getValue($field), $attributes);
    }
    
    /**
     * Generate select field
     */
    public function select($field, $options = [], $empty = null) {
        $attributes = array_merge([
            'name' => $field,
            'id' => $field,
            'class' => 'form-select'
        ], $this->getAttributes($field));
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        $html = '';
        
        if ($empty !== null) {
            $html .= $this->tag('option', $empty, ['value' => '']);
        }
        
        foreach ($options as $value => $label) {
            $optionAttributes = ['value' => $value];
            
            if ($value == $this->getValue($field)) {
                $optionAttributes['selected'] = 'selected';
            }
            
            $html .= $this->tag('option', $label, $optionAttributes);
        }
        
        return $this->tag('select', $html, $attributes);
    }
    
    /**
     * Generate checkbox
     */
    public function checkbox($field, $value = '1', $label = null) {
        $attributes = array_merge([
            'type' => 'checkbox',
            'name' => $field,
            'id' => $field,
            'value' => $value,
            'class' => 'form-check-input'
        ], $this->getAttributes($field));
        
        if ($this->getValue($field) == $value) {
            $attributes['checked'] = 'checked';
        }
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        $input = $this->tag('input', '', $attributes);
        
        if ($label) {
            $input = $this->tag('div', 
                $input . $this->tag('label', $label, [
                    'for' => $field,
                    'class' => 'form-check-label'
                ]),
                ['class' => 'form-check']
            );
        }
        
        return $input;
    }
    
    /**
     * Generate radio button
     */
    public function radio($field, $value, $label = null) {
        $attributes = array_merge([
            'type' => 'radio',
            'name' => $field,
            'id' => "{$field}_{$value}",
            'value' => $value,
            'class' => 'form-check-input'
        ], $this->getAttributes($field));
        
        if ($this->getValue($field) == $value) {
            $attributes['checked'] = 'checked';
        }
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        $input = $this->tag('input', '', $attributes);
        
        if ($label) {
            $input = $this->tag('div',
                $input . $this->tag('label', $label, [
                    'for' => "{$field}_{$value}",
                    'class' => 'form-check-label'
                ]),
                ['class' => 'form-check']
            );
        }
        
        return $input;
    }
    
    /**
     * Generate file input
     */
    public function file($field) {
        $attributes = array_merge([
            'type' => 'file',
            'name' => $field,
            'id' => $field,
            'class' => 'form-control'
        ], $this->getAttributes($field));
        
        if ($this->getError($field)) {
            $attributes['class'] .= ' is-invalid';
        }
        
        return $this->tag('input', '', $attributes);
    }
    
    /**
     * Generate label
     */
    public function label($field) {
        return $this->tag('label', $this->getLabel($field), [
            'for' => $field,
            'class' => 'form-label'
        ]);
    }
    
    /**
     * Generate error message
     */
    public function error($field) {
        $error = $this->getError($field);
        if (!$error) {
            return '';
        }
        
        return $this->tag('div', $error, ['class' => 'invalid-feedback']);
    }
    
    /**
     * Generate form group
     */
    public function group($field, $type = 'text', $options = []) {
        $html = $this->label($field);
        
        switch ($type) {
            case 'textarea':
                $html .= $this->textarea($field);
                break;
            case 'select':
                $html .= $this->select($field, $options);
                break;
            case 'file':
                $html .= $this->file($field);
                break;
            default:
                $html .= $this->input($field, $type);
        }
        
        $html .= $this->error($field);
        
        return $this->tag('div', $html, ['class' => 'mb-3']);
    }
    
    /**
     * Create HTML tag
     */
    protected function tag($name, $content = '', $attributes = []) {
        $html = "<$name";
        
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html .= " $key";
            } elseif ($value !== false) {
                $html .= " $key=\"" . htmlspecialchars($value) . "\"";
            }
        }
        
        if ($content === false) {
            return "$html />";
        }
        
        return "$html>$content</$name>";
    }
}
