<h2>Orphaned Match Reports</h2>

<div class="dashboard-orphaned-match-reports">
  <ul>
    <% loop $MatchReports %>
      <li><a href="$Link">$Title (MR)</a></li>
    <% end_loop %>
  </ul>
</div>
