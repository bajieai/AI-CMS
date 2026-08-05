async ({ page }) => {
  await page.fill('input[name="username"]', 'test');
  await page.fill('input[name="password"]', 'test123');
  await page.click('button:has-text("登录")');
  await page.waitForTimeout(3000);
  return page.url();
}
