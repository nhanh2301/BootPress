{$page->plugin('jQuery', ['version'=>'2.1.3', 'ui'=>'1.11.2', 'code'=>'$.widget.bridge("uibutton", $.ui.button);'])}

{$page->plugin('CDN', 'links', [
  'icheck/1.0.2/icheck.min.js',
  'icheck/1.0.2/skins/line/red.min.css',
  'slimscroll/1.3.3/jquery.slimscroll.min.js',
  'fastclick/1.0.3/fastclick.min.js',
  'fontawesome/4.3.0/css/font-awesome.min.css'
])}

{$page->link([
  $page->url('theme', 'bootstrap/css/bootstrap.min.css'),
  $page->url('theme', 'bootstrap/js/bootstrap.min.js'),
  $page->url('theme', 'dist/css/AdminLTE.min.css'),
  $page->url('theme', 'dist/css/skins/skin-blue.min.css'),
  $page->url('theme', 'dist/js/app.min.js'),
  '<!--[if lt IE 9]>
      <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
  <![endif]-->'
], 'prepend')}

{$page->meta('content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport"')}

{$page->style([
  '.navbar-text { margin-bottom:12px; margin-top:11px; }',
  'ul.sidebar-menu, div.navbar-custom-menu { font-size:16px; }',
  'ul.sidebar-menu .fa { margin-right:10px; }',
  'ul.sidebar-menu > li { padding-left:5px; }'
])}
{$page->set('body', 'class="skin-blue fixed"')}

<div class="wrapper">
  
  <header class="main-header">
    <div class="logo"><a href="http://www.bootpress.org">BootPress</a></div>
    <nav class="navbar navbar-static-top" role="navigation">
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"><span class="sr-only">Toggle navigation</span></a>
      {$page->navbar}
    </nav>
  </header>
  
  <aside class="main-sidebar"><section class="sidebar">{$page->sidebar}</section></aside>
  
  <div class="content-wrapper">
    <section class="content-header">{$page->header}</section>
    <section class="content">{$content}</section>
  </div>
  
  {$page->footer}
  
</div>