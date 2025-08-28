(() => {
  const buttons = Array.from(document.querySelectorAll('div[aria-label="Invite"]'));
  document.querySelectorAll('button').forEach(btn => {
    if (/invite/i.test(btn.textContent) && !buttons.includes(btn)) {
      buttons.push(btn);
    }
  });
  buttons.forEach(btn => {
    btn.click();
  });
  console.log(`Invited ${buttons.length} users.`);
})();
