import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';

(async () => {
    const args = process.argv.slice(2);
    const inputHtml = args[0];
    const outputPdf = args[1];

    if (!inputHtml || !outputPdf) {
        console.error('Usage: node generate_pdf.js <input.html> <output.pdf>');
        process.exit(1);
    }

    const htmlPath = path.resolve(inputHtml);
    const pdfPath = path.resolve(outputPdf);

    if (!fs.existsSync(htmlPath)) {
        console.error('Input HTML file does not exist: ' + htmlPath);
        process.exit(1);
    }

    const html = fs.readFileSync(htmlPath, 'utf8');
    if (!html.trim()) {
        console.error('Input HTML file is empty.');
        process.exit(1);
    }

    let browser;
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--font-render-hinting=none',
                '--disable-gpu',
            ],
        });
        const page = await browser.newPage();
        await page.setViewport({ width: 794, height: 1123, deviceScaleFactor: 1 });
        await page.emulateMediaType('print');
        await page.setContent(html, {
            waitUntil: 'load',
            timeout: 60000,
        });
        await page.pdf({
            path: pdfPath,
            format: 'A4',
            printBackground: true,
            preferCSSPageSize: false,
            margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' },
        });
        await browser.close();
        console.log('PDF generated successfully.');
    } catch (error) {
        if (browser) {
            try { await browser.close(); } catch (e) {}
        }
        console.error('Error generating PDF:', error);
        process.exit(1);
    }
})();
