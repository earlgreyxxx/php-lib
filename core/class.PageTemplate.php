<?php
/*******************************************************************************

  Derivered from TemplateBase class for sample web application

*******************************************************************************/

class PageTemplate extends TemplateBase
{
  private function getMaxPage() : int
  {
    return intval($this['length'] > 0 ? ceil($this['length'] / $this['max_per_page']) : 1);
  }

  private function getPagination() : string
  {
    $pagingUrl = $this['pagingUrl'];
    $current_page = intval($this['page']);
    $max_per_page = intval($this['max_per_page']);
    $length = intval($this['length']);
    if($length <= 0)
      return '';

    $delm = false === strpos($pagingUrl,'?') ? '?' : '&';
    $max_page = $length > 0 ? ceil($length / $max_per_page) : 1;

    $rv = [];

    $delta = PAGE_NAVI_DELTA;
    $start = $current_page - $delta;
    $end = $current_page + $delta;
    if( $start < 1)
    {
      $end += abs($start-1);
      $start = 1;
    }

    if($end > $max_page)
    {
      if($start - ($end - $max_page) >= 1)
        $start -= ($end - $max_page);
      $end = $max_page;
    }

    if($current_page > 1)
    {
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%s\">&#xf100;</a></li>",$pagingUrl,$delm);
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf104;</a></li>",$pagingUrl,$delm,$current_page - 1);
    }

    for($i=$start;$i<=$end;$i++)
    {
      if($i == $current_page)
        $rv[] = sprintf('<li class="page-item active current"><span class="page-link">%d</span></li>',$i);
      else
        $rv[] = sprintf("<li class=\"page-item\"><a class=\"page-link\" href=\"%s%sp=%d\">%d</a></li>",$pagingUrl,$delm,$i,$i);
    }

    if($current_page < $max_page)
    {
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf105;</a></li>",$pagingUrl,$delm,$current_page + 1);
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf101;</a></li>",$pagingUrl,$delm,$max_page);
    }

    return implode("\n",$rv);
  }

  // paging data given
  public function setPagingInfo(int $item_length,int $max_per_page = 10,int $current_page = 1,string $url = '') : void
  {
    $this['page'] = $current_page;
    $this['max_per_page'] = $max_per_page;
    $this['length'] = $item_length;
    $this['pagingUrl'] = $url;
  }

  public function maxPage(string $before = '',string $after = '') : void
  {
    echo $before,$this->getMaxPage(),$after;
  }

  public function pagination(string $before = '<ul>',string $after = '</ul>',bool $force = true) : void
  {
    if(!$force && $this->getMaxPage() <= 1)
      return;

    echo $before,"\n";
    echo "    ",$this->getPagination();
    echo "\n  ",$after;
  }

  protected function buildAttributes(array $attrs) : string
  {
    $attrib = '';
    if(!empty($attrs))
    {
      $attribs = array('');
      foreach($attrs as $n => $v)
      {
        if(is_numeric($n))
          $attribs[] = $v;
        else
          $attribs[] = sprintf('%s="%s"',$n,htmlspecialchars($v,ENT_QUOTES));
      }
      $attrib = implode(' ',$attribs);
    }

    return $attrib;
  }

  protected function getStartTag(string $tagname,array $attrs = []) : string
  {
    $attrib = $this->buildAttributes($attrs);
    return sprintf('<%s%s>',$tagname,$attrib);
  }
  protected function getEndTag(string $tagname) : string
  {
    return sprintf('</%s>',$tagname);
  }

  protected function getTag(string $tagname,string $content,array $attrs = []) : string
  {
    return $this->getStartTag($tagname,$attrs).$content.$this->getEndTag($tagname);
  }

  protected function tag(string $tagname,string $content,array $attrs = []) : void
  {
    echo $this->getTag($tagname,$content,$attrs),PHP_EOL;
  }
  protected function tagVal(string $tagname,string $propname,array $attrs = []) : void
  {
    $this->tag($tagname,$this[$propname],$attrs);
  }
}
