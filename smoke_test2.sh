for route in '/' '/play' '/hall-of-fame' '/hall-of-fame/rankings' '/dashboard/stats' '/history/wcoin' '/announcements/latest' '/playgame/1'; do
  echo "$route => "
  curl -s -o /dev/null -w "%{http_code}" -H "Host: muh5-stage.ccgame.org" "http://127.0.0.1$route"
  echo
done
