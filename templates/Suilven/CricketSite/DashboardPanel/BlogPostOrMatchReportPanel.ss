<h2>Blog Post or Match Report? - $Amount</h2>

<div class="dashboard-blogpost-or-match-report">
  <ul>
    <% loop $BlogPosts %>
      <li><a href="$Link">
        <% if $ClassName == 'SilverStripe\Blog\Model\BlogPost' %><b>B</b><% end_if %>
        <% if $ClassName == 'Suilven\CricketSite\Model\MatchReport' %><b>M</b><% end_if %>
        $PublishDate.Format('d-M-y') $Title</a></li>
    <% end_loop %>
  </ul>
</div>
