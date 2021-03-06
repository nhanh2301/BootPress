<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_themes extends CI_Driver {

  private $dir;
  private $docs;
  private $theme = false;
  private $bootstrap;
  private $files;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $html = '';
    $this->dir = BASE_URI . 'themes/';
    $this->docs = $bp->button('md link', 'Documentation ' . $bp->icon('new-window'), array('href'=>'https://www.bootpress.org/docs/themes/', 'target'=>'_blank'));
    if (isset($params['theme'])) {
      $this->theme = $this->mktheme($params['theme']);
      $this->bootstrap = BASE . 'bootstrap/' . $ci->blog->bootstrap . '/';
      if (isset($params['less'])) {
        $less = $this->less($this->theme);
        header('Content-Type: text/css');
        header('Content-Length: ' . strlen($less));
        exit($less);
      };
      if (isset($params['action'])) {
        switch ($params['action']) {
          case 'download': return $this->download(); break;
          case 'preview': return $this->preview(); break;
        }
      }
      $media = $this->files();
      if ($ci->input->get('image')) {
        $html .= $media;
      } else {
        $html .= $bp->row('lg', array(
          $bp->col(6, $this->theme()),
          $bp->col(6, $this->bootstrap())
        )) . $media;
      }
    } else {
      $html .= $this->create();
    }
    return $this->display($html);
  }
  
  public function update () {
    global $ci;
    $ci->sitemap->suspend_caching(0);
  }
  
  private function mktheme ($theme, $unzip=null) {
    global $ci, $page;
    $theme = $page->seo($theme);
    if (!is_dir($this->dir . $theme)) mkdir($this->dir . $theme, 0755, true);
    if (!is_file($this->dir . $theme . '/index.tpl')) {
      if ($unzip && is_file($unzip)) {
        $ci->load->library('unzip');
        $ci->unzip->files($unzip, $this->dir . $theme, 0755);
        $ci->unzip->extract('tpl|js|css|less|ttf|otf|svg|eot|woff|swf|jpg|jpeg|gif|png|ico', $ci->unzip->common_dir());
        $ci->unzip->close();
      } else {
        file_put_contents($this->dir . $theme . '/index.tpl', file_get_contents($ci->blog->templates . 'theme/index.tpl'));
        file_put_contents($this->dir . $theme . '/blog.css', file_get_contents($ci->blog->templates . 'theme/blog.css'));
      }
    }
    return $theme;
  }
  
  private function files () {
    global $bp, $ci, $page;
    $this->files = $ci->admin->files->save(array(
      'index' => array($this->dir . $this->theme . '/index.tpl', $ci->blog->templates . 'theme/index.tpl')
    ), array('index'), array($this, 'update'));
    $this->files = array_merge($this->files, $ci->admin->files->save(array(
      'bootstrap' => array($this->dir . $this->theme . '/variables.less', $this->bootstrap . 'less/variables.less'),
      'custom' => $this->dir . $this->theme . '/custom.less'
    ), array('bootstrap', 'custom')));
    $media = $ci->admin->files->view('themes', $this->dir . $this->theme);
    if ($ci->input->get('image')) {
      return $this->box('default', array(
        'head with-border' => $bp->icon('image', 'fa') . ' Image',
        'body' => $media
      ));
    } else {
      return $this->box('default', array(
        'head with-border' => array('Files'),
        'body' => $media
      ));
    }
  }
  
  private function select () {
    global $ci, $page;
    $form = $page->plugin('Form', 'name', 'admin_theme_select');
    $themes = array(
      $page->url('admin', 'themes') => '',
      $page->url('admin', 'themes', 'default') => 'default'
    );
    list($dirs) = $ci->blog->folder($this->dir, false, false);
    foreach ($dirs as $theme) $themes[$page->url('admin', 'themes', $theme)] = $theme;
    $form->menu('themes', $themes);
    $form->values('themes', $page->url('admin', 'themes', $this->theme));
    $form->validate('themes', ($this->theme ? 'Edit' : 'Select'), '', 'Select the theme you would like to edit.');
    $page->plugin('jQuery', 'code', '$("#' . $form->id('themes') . '").change(function(){ window.location = $(this).val(); });');
    return $form->field('themes', 'select');
  }
  
  private function create () {
    global $bp, $ci, $page;
    $form = $page->plugin('Form', 'name', 'admin_theme_create');
    $form->upload('upload', 'Upload', 'zip', array(
      'info' => 'Submit a zipped file to extract for your theme.',
      'filesize' => 10,
      'limit' => 1
    ));
    $form->validate('create', 'Create', 'required', 'Enter the name of the theme you would like to create.');
    if ($form->submitted() && empty($form->errors)) {
      $theme = $form->vars['create'];
      $unzip = (!empty($form->vars['upload'])) ? key($form->vars['upload']) : null;
      $page->eject($page->url('admin', 'themes', $this->mktheme($theme, $unzip)));
    }
    return $this->box('default', array(
      'head with-border' => array($bp->icon('desktop', 'fa') . ' Themes', $this->docs),
      'body' => implode('', array(
        $form->header(),
        $this->select(),
        $form->field('create', 'text'),
        $form->field('upload', 'file'),
        $form->submit(),
        $form->close()
      ))
    ));
  }
  
  private function theme () {
    global $bp, $ci, $page;
    if ($ci->input->get('delete') == 'theme') {
      if (is_dir($this->dir . $this->theme)) {
        list($dirs, $files) = $ci->blog->folder($this->dir . $this->theme, 'recursive');
        arsort($dirs);
        foreach ($files as $file) unlink($this->dir . $this->theme . $file);
        foreach ($dirs as $dir) rmdir($this->dir . $this->theme . $dir);
        rmdir($this->dir . $this->theme);
      }
      $page->eject($page->url('admin', 'themes'));
    }
    if (($preview = $ci->input->post('preview')) && $ci->input->is_ajax_request()) {
      if ($preview == 'true') {
        $ci->sitemap->suspend_caching(60);
        $ci->session->preview_layout = $this->theme;
        $ci->session->mark_as_temp('preview_layout', 3000);
      } else { // $preview == 'false'
        unset($_SESSION['preview_layout']);
      }
      exit;
    } elseif ($ci->session->preview_layout) {
      $ci->session->preview_layout = $this->theme;
      $ci->session->mark_as_temp('preview_layout', 3000);
    }
    $form = $page->plugin('Form', 'name', 'admin_theme_manage');
    $form->menu('preview', array('Y'=>'Preview the selected theme'));
    $form->menu('action', array(
      'copy' => '<b>Copy</b> will make a duplicate of this theme if it does not already exist',
      'rename' => '<b>Rename</b> will change the name of this theme as long as it does not already exist',
      'swap' => '<b>Swap</b> will exchange this theme with the one you want to save as long as it actually exists'
    ));
    $form->validate(
      array('preview', '', 'YN'),
      array('index', 'index.tpl', '', 'This file creates the layout for your content.'),
      array('save', 'Save As', 'required', 'Enter the name of the theme for which you would like to Copy, Rename, or Swap.'),
      array('action', '', 'required|inarray[menu]')
    );
    $form->values($this->files);
    $form->values(array(
      'preview' => ($ci->session->preview_layout) ? 'Y' : 'N',
      'action' => 'copy'
    ));
    if ($form->submitted() && empty($form->errors)) {
      if (!empty($form->vars['save'])) {
        $new_theme = $page->seo($form->vars['save']);
        $exists = (is_dir($this->dir . $new_theme)) ? true : false;
        switch ($form->vars['action']) {
          case 'copy':
            if (!$exists) {
              mkdir($this->dir . $new_theme, 0755, true);
              list($dirs, $files) = $ci->blog->folder($this->dir . $this->theme, 'recursive');
              foreach ($dirs as $dir) mkdir($this->dir . $new_theme . $dir, 0755, true);
              foreach ($files as $file) copy($this->dir . $this->theme . $file, $this->dir . $new_theme . $file);
            } else {
              $form->errors['action'] = 'The theme name you are trying to <b>Save As</b> a <b>Copy</b> already exists.';
            }
            break;
          case 'rename':
            if (!$exists) {
              rename($this->dir . $this->theme, $this->dir . $new_theme);
            } else {
              $form->errors['action'] = 'You cannot <b>Rename</b> and <b>Save As</b> a theme that already exists.';
            }
            break;
          case 'swap':
            if ($exists) {
              $temp = md5($this->dir . $this->theme) . microtime();
              rename($this->dir . $this->theme, $this->dir . $temp);
              rename($this->dir . $new_theme, $this->dir . $this->theme);
              rename($this->dir . $temp, $this->dir . $new_theme);
            } else {
              $form->errors['action'] = 'The <b>Save As</b> theme you are <b>Swap</b>ping with does not exist.';
            }
        }
        if (empty($form->errors)) $page->eject($page->url('admin', 'themes', $new_theme));
      } else { // $form->vars['save'] is empty
        $this->update();
        $page->eject($form->eject);
      }
    }
    $page->plugin('jQuery', 'code', '
      $("input[name=preview]").change(function(){
        var checked = $(this).is(":checked") ? "true" : "false";
        $.post(location.href, {preview:checked});
      });
      $(".delete").click(function(){
        if (confirm("Are you sure you would like to delete this theme?")) {
          window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'theme')) . '";
        }
      });
    ');
    return $this->box('default', array(
      'head with-border' => array($bp->icon('desktop', 'fa') . ' ' . ucwords($this->theme) . ' Theme', $this->docs),
      'body' => implode('', array(
        $form->header(),
        $form->field(false,
          str_replace('class="checkbox"', 'class="checkbox pull-left"', $form->field('preview', 'checkbox', array('label'=>false))) .
          $bp->button('danger delete pull-right', $bp->icon('trash'), array('title'=>'Click to delete this theme'))
        ),
        $this->select(),
        $form->field('save', 'text'),
        $form->field('action', 'radio'),
        $form->submit('Submit', $bp->button('info pull-right', 'Download ' . $bp->icon('download'), array('href'=>$page->url('admin', 'themes/download', $this->theme)))),
        $form->field('index', 'textarea', array('class'=>'wyciwyg tpl input-sm', 'data-file'=>'index.tpl', 'rows'=>22)),
        $form->close()
      ))
    ));
  }
  
  private function bootstrap () {
    global $bp, $ci, $page;
    $form = $page->plugin('Form', 'name', 'admin_bootstrap');
    $form->values($this->files);
    $form->validate(
      array('bootstrap', 'variables.less', 'required', 'This is the Twitter Bootstrap variables.less file that you may edit to roll out your own theme.  Currently serving v' . $ci->blog->bootstrap . '.  When you compile below, just sit still and relax.  It will take a minute or so.'),
      array('custom', 'custom.less', '', 'This is LESS CSS that is processed with the <b>Variables</b> above, and placed in the compiled <b>bootstrap-' . $ci->blog->bootstrap . '.css</b> below.  You may use any of the same variables and mixins that Bootstrap uses, and / or create your own.')
    );
    if ($form->submitted() && empty($form->errors)) {
      include_once BASE . 'bootstrap/less.php/Less.php';
      try {
        $parser = new Less_Parser(array('compress'=>true));
        $parser->parse($this->less($this->theme, 'custom'));
        $css = $parser->getCss();
        file_put_contents($this->dir . $this->theme . '/bootstrap-' . $ci->blog->bootstrap . '.css', $css);
        $form->message('success', 'Your bootstrap-' . $ci->blog->bootstrap . '.css file has been compiled and saved below.');
      } catch (Exception $e) {
        $form->message('danger', 'Compile Error: ' . $e->getMessage());
      }
      $page->eject($form->eject);
    }
    return $this->box('default', array(
      'head with-border' => array('Bootstrap'),
      'body' => implode('', array(
        $form->header(),
        $form->field(false,
          $bp->button('info pull-left', 'Preview Less Variables ' . $bp->icon('new-window'), array('href'=>$page->url('admin', 'themes/preview', $this->theme), 'target'=>'_bootstrap')) .
          $bp->button('primary pull-right', 'Compile', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))
        ),
        $form->field('bootstrap', 'textarea', array('class'=>'wyciwyg less input-sm', 'data-file'=>'variables.less', 'rows'=>23, 'style'=>'padding-bottom:6px;')),
        $form->field('custom', 'textarea', array('class'=>'wyciwyg less input-sm', 'data-file'=>'custom.less')),
        $form->close()
      ))
    ));
  }
  
  private function download () {
    global $ci, $page;
    $user = $ci->session->analytics;
    $ci->load->library('zip');
    $ci->zip->compression_level = 9;
    $ci->zip->read_dir($this->dir . $this->theme, false);
    $ci->zip->download('backup-theme-' . $page->get('domain') . '-' . $this->theme . '-' . date('Y-m-d_H-i-s', time() - $user['offset']) . '.zip');
  }
  
  private function preview () {
    global $ci, $page;
    $page->link('<script>var less = { env:"development" };</script>');
    $page->link('<script src="' . $page->plugin('CDN', 'url', 'less/2.2.0/less.min.js') . '"></script>');
    $page->link('<link rel="stylesheet/less" type="text/css" href="' . $page->url('admin', 'themes/preview', $this->theme, 'bootstrap.less') . '">');
    return $page->outreach(BASE . 'bootstrap/preview.php');
  }
  
  private function less ($theme, $custom=false) {
    if (!is_file($this->bootstrap . 'bootpress.less')) {
      $less = $this->bootstrap . 'less/';
      $bootpress = array();
      preg_match_all('/@import\s*(.*);/i', file_get_contents($less . 'mixins.less'), $matches);
      foreach ($matches[1] as $import) $bootpress[] = file_get_contents($less . trim($import, '"'));
      preg_match_all('/@import\s*(.*);/i', file_get_contents($less . 'bootstrap.less'), $matches);
      foreach (array_splice($matches[1], 2) as $import) $bootpress[] = file_get_contents($less . trim($import, '"'));
      file_put_contents($this->bootstrap . 'bootpress.less', implode("\n\n", $bootpress));
    }
    if (is_file($this->dir . $theme . '/variables.less')) {
      $less = $this->merge_variables(file_get_contents($this->dir . $theme . '/variables.less'));
    } else {
      $less = $this->merge_variables('');
    }
    file_put_contents($this->dir . $theme . '/variables.less', $less);
    $less = array($less, file_get_contents($this->bootstrap . 'bootpress.less'));
    if ($custom !== false) {
      if (is_file($this->dir . $theme . '/custom.less')) {
        $less[] = file_get_contents($this->dir . $theme . '/custom.less');
      }
    }
    return implode("\n\n", $less);
  }
  
  private function merge_variables ($less) {
    global $ci, $page;
    #-- Submitted $less variables --#
    $variables = array();
    if (preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    $variables['icon-font-path'] = '"' . dirname($page->plugin('CDN', 'url', 'bootstrap/' . $ci->blog->bootstrap . '/fonts/glyphicons-halflings-regular.eot')) . '/"';
    #-- The default (master) variables --#
    $file = file_get_contents($this->bootstrap . 'less/variables.less');
    preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $file, $matches);
    $defaults = array_flip($matches[1]);
    foreach ($variables as $var => $value) {
      if (isset($defaults[$var])) {
        $key = $defaults[$var];
        $original = trim($matches[2][$key]);
        if ($original != $value) {
          $replace = substr($matches[0][$key], 0, strrpos($matches[0][$key], $original)) . $value . '; // ' . $original . ';';
          $file = str_replace($matches[0][$key], $replace, $file);
        }
        unset($variables[$var]);
      }
    }
    #-- Submitted variables that were not in the master file --#
    if (!empty($variables)) {
      $lengths = array();
      foreach ($variables as $var => $value) $lengths[] = strlen($var);
      $pad = max($lengths) + 4;
      foreach ($variables as $var => $value) $variables[$var] = '@' . str_pad($var . ':', $pad, ' ') . $value . ';';
      $file = "// Custom\n// --------------------------------------------------\n" . implode("\n", $variables) . "\n\n\n" . $file;
    }
    #-- Place the Imports up top --#
    if (preg_match_all('/@import\s*(.*);/i', $less, $matches)) {
      $imports = $matches[0];
      $file = "// Import(s)\n// --------------------------------------------------\n" . implode("\n", $imports) . "\n\n\n" . $file;
    }
    #-- Return the $less with all of the required variables included --#
    return $file;
  }
  
}

/* End of file Admin_themes.php */
/* Location: ./application/libraries/Admin/drivers/Admin_themes.php */