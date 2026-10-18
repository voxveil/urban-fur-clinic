<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics & Reports - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=18">
</head>
<body>

  <!-- Navigation Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
    
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item active"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1>Reports & Analytics</h1>
        <p>Review clinic sales, outstanding balances, and operational records.</p>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-peso-sign"></i></span><div class="stat-info"><h3 id="report-weekly-sales">₱0.00</h3><p>Paid Sales in Range</p></div></div>
      <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-receipt"></i></span><div class="stat-info"><h3 id="report-transactions">0</h3><p>Transactions in Range</p></div></div>
      <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-clock"></i></span><div class="stat-info"><h3 id="report-credit">₱0.00</h3><p>Pay Later Balance in Range</p></div></div>
      <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-chart-line"></i></span><div class="stat-info"><h3 id="report-monthly-sales">0</h3><p>Records in Report</p></div></div>
    </div>

    <section class="settings-help report-calendar-filter" id="reportCalendarFilter" aria-label="Report date filter">
      <div class="report-calendar-title">
        <i class="fa-regular fa-calendar"></i>
        <strong>Report date range</strong>
      </div>
      <label>
        <span>Period</span>
        <select id="reportPeriod" class="filter-select">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly" selected>Monthly</option>
          <option value="custom">Custom date range</option>
        </select>
      </label>
      <label id="reportDayControl">
        <span>Date</span>
        <input type="date" id="reportDay" class="date-filter">
      </label>
      <label id="reportMonthControl">
        <span>Month</span>
        <input type="month" id="reportMonth" class="date-filter">
      </label>
      <label id="reportWeekControl" hidden>
        <span>Week of</span>
        <input type="date" id="reportWeek" class="date-filter">
      </label>
      <div id="reportCustomControl" class="report-custom-range" hidden>
        <label><span>From</span><input type="date" id="reportStartDate" class="date-filter"></label>
        <label><span>To</span><input type="date" id="reportEndDate" class="date-filter"></label>
      </div>
      <button class="btn-primary" type="button" id="applyReportDateButton">Apply dates</button>
    </section>
    <section class="settings-help" id="reportGenerator">
      <div class="report-generator-copy">
        <span class="report-generator-icon"><i class="fa-solid fa-file-lines"></i></span>
        <div><strong>Generate a report</strong><p>Select a dataset to view or export.</p></div>
      </div>
      <div class="report-generator-actions">
        <label class="visually-hidden" for="reportType">Report type</label>
        <select id="reportType" class="filter-select">
          <option value="sales">Sales Report</option>
          <option value="inventory">Inventory Report</option>
          <option value="clients">Clients Report</option>
          <option value="patients">Patients Report</option>
          <option value="credit">Pay Later / Credit Report</option>
        </select>
        <button class="btn-primary" type="button" id="generateReportButton"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
        <button class="btn-secondary" type="button" id="exportReportButton" disabled><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      </div>
    </section>
    <div class="table-container">
      <table>
        <thead id="reportTableHead"></thead>
        <tbody id="reportTableBody"><tr><td>Select a report and choose Generate.</td></tr></tbody>
      </table>
    </div>
  </main>
  <script src="assets/app.js?v=5"></script>
  <script>
    const reportDefinitions = {
      sales: {
        resource: 'invoices',
        columns: [
          ['id', 'Invoice'], ['date', 'Date'], ['client', 'Client'], ['pet', 'Patient'],
          ['payment_method', 'Payment Method'], ['status', 'Status'], ['amount', 'Amount (₱)']
        ],
        map: rows => rows.map(invoice => ({ ...invoice, amount: Number(invoice.amount).toFixed(2) }))
      },
      inventory: {
        resource: 'inventory',
        columns: [
          ['sku', 'SKU'], ['name', 'Item'], ['category', 'Category'], ['quantity', 'Units in Stock'],
          ['min_threshold', 'Minimum Stock'], ['price', 'Unit Price (₱)']
        ],
        map: rows => rows.map(item => ({ ...item, price: Number(item.price).toFixed(2) })),
        dateKey: 'created_at'
      },
      clients: {
        resource: 'clients',
        columns: [['name', 'Client'], ['phone', 'Phone'], ['email', 'Email'], ['status', 'Status'], ['pet_count', 'Patients']],
        map: rows => rows,
        dateKey: 'created_at'
      },
      patients: {
        resource: 'pets',
        columns: [['name', 'Patient'], ['species', 'Species'], ['breed', 'Breed'], ['owner', 'Owner'], ['status', 'Status']],
        map: rows => rows,
        dateKey: 'created_at'
      },
      credit: {
        resource: 'invoices',
        columns: [
          ['id', 'Invoice'], ['date', 'Date'], ['client', 'Client'], ['pet', 'Patient'],
          ['service', 'Items'], ['status', 'Status'], ['amount', 'Balance (₱)']
        ],
        map: rows => rows
          .filter(invoice => invoice.payment_method === 'Pay Later' && ['Pending', 'Overdue'].includes(invoice.status))
          .map(invoice => ({ ...invoice, amount: Number(invoice.amount).toFixed(2) })),
        dateKey: 'date'
      }
    };

    let currentReportRows = [];
    let currentReportDefinition = null;
    let currentDateRange = null;
    let preferredExportFormat = 'csv';
    const formatPeso = value => `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const localDate = date => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    const currentDate = localDate(new Date());
    document.getElementById('reportDay').value = currentDate;
    document.getElementById('reportWeek').value = currentDate;
    document.getElementById('reportMonth').value = currentDate.slice(0, 7);

    function getSelectedDateRange() {
      const period = document.getElementById('reportPeriod').value;
      if (period === 'daily') {
        const date = document.getElementById('reportDay').value;
        return date ? { start: date, end: date } : null;
      }
      if (period === 'monthly') {
        const month = document.getElementById('reportMonth').value;
        if (!month) return null;
        const [year, monthNumber] = month.split('-').map(Number);
        return { start: `${month}-01`, end: localDate(new Date(year, monthNumber, 0)) };
      }
      if (period === 'weekly') {
        const date = document.getElementById('reportWeek').value;
        if (!date) return null;
        const selected = new Date(`${date}T00:00:00`);
        const weekday = (selected.getDay() + 6) % 7;
        selected.setDate(selected.getDate() - weekday);
        const end = new Date(selected);
        end.setDate(end.getDate() + 6);
        return { start: localDate(selected), end: localDate(end) };
      }
      const start = document.getElementById('reportStartDate').value;
      const end = document.getElementById('reportEndDate').value;
      if (!start || !end || start > end) throw new Error('Choose a valid custom date range.');
      return { start, end };
    }

    function updateReportDateControls() {
      const period = document.getElementById('reportPeriod').value;
      document.getElementById('reportDayControl').hidden = period !== 'daily';
      document.getElementById('reportMonthControl').hidden = period !== 'monthly';
      document.getElementById('reportWeekControl').hidden = period !== 'weekly';
      document.getElementById('reportCustomControl').hidden = period !== 'custom';
      document.getElementById('applyReportDateButton').hidden = period !== 'custom';
    }

    async function initializeReports() {
      try {
        const settings = await clinicRequest('settings');
        preferredExportFormat = settings.data.reports_export_format || 'csv';
        document.getElementById('reportPeriod').value = settings.data.reports_default_period || 'monthly';
        updateReportDateControls();
        setExportButtonLabel();
        await generateReport();
      } catch (error) {
        clinicShowError(error);
      }
    }

    function setExportButtonLabel() {
      const labels = { csv: 'Export CSV', excel: 'Export Excel', pdf: 'Save as PDF' };
      document.getElementById('exportReportButton').innerHTML = `<i class="fa-solid fa-file-export"></i> ${labels[preferredExportFormat] || labels.csv}`;
    }

    async function generateReport() {
      const button = document.getElementById('generateReportButton');
      const definition = reportDefinitions[document.getElementById('reportType').value];
      button.disabled = true;
      try {
        currentDateRange = getSelectedDateRange();
        if (!currentDateRange) throw new Error('Choose a date to generate this report.');
        const [result, invoiceResult] = await Promise.all([
          clinicRequest(definition.resource),
          definition.resource === 'invoices' ? Promise.resolve(null) : clinicRequest('invoices')
        ]);
        currentReportDefinition = definition;
        currentReportRows = definition.map(result.data);
        if (definition.dateKey) {
          currentReportRows = currentReportRows.filter(row => {
            const date = String(row[definition.dateKey] || '').slice(0, 10);
            return date >= currentDateRange.start && date <= currentDateRange.end;
          });
        } else if (definition.resource === 'invoices') {
          currentReportRows = currentReportRows.filter(row => {
            const date = String(row.date || '').slice(0, 10);
            return date >= currentDateRange.start && date <= currentDateRange.end;
          });
        }
        document.getElementById('reportTableHead').innerHTML = `<tr>${definition.columns.map(([, label]) => `<th>${clinicEscape(label)}</th>`).join('')}</tr>`;
        document.getElementById('reportTableBody').innerHTML = currentReportRows.length
          ? currentReportRows.map(row => `<tr>${definition.columns.map(([key]) => `<td>${clinicEscape(row[key] ?? '')}</td>`).join('')}</tr>`).join('')
          : `<tr><td colspan="${definition.columns.length}">No records found for this report.</td></tr>`;
        const invoiceRows = (invoiceResult ? invoiceResult.data : result.data).filter(invoice => {
          const date = String(invoice.date || '').slice(0, 10);
          return date >= currentDateRange.start && date <= currentDateRange.end;
        });
        document.getElementById('report-weekly-sales').textContent = formatPeso(invoiceRows
          .filter(invoice => invoice.status === 'Paid')
          .reduce((total, invoice) => total + Number(invoice.amount || 0), 0));
        document.getElementById('report-transactions').textContent = invoiceRows.length.toLocaleString();
        document.getElementById('report-credit').textContent = formatPeso(invoiceRows
          .filter(invoice => invoice.payment_method === 'Pay Later' && ['Pending', 'Overdue'].includes(invoice.status))
          .reduce((total, invoice) => total + Number(invoice.amount || 0), 0));
        document.getElementById('report-monthly-sales').textContent = currentReportRows.length.toLocaleString();
        document.getElementById('exportReportButton').disabled = currentReportRows.length === 0;
      } catch (error) {
        clinicShowError(error);
      } finally {
        button.disabled = false;
      }
    }

    function exportReportCsv() {
      if (!currentReportDefinition || currentReportRows.length === 0) return;
      if (preferredExportFormat === 'pdf') {
        window.print();
        return;
      }
      const quote = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
      const rows = [
        currentReportDefinition.columns.map(([, label]) => quote(label)),
        ...currentReportRows.map(row => currentReportDefinition.columns.map(([key]) => quote(row[key])))
      ];
      const excelRows = [
        currentReportDefinition.columns.map(([, label]) => `<th>${clinicEscape(label)}</th>`),
        ...currentReportRows.map(row => currentReportDefinition.columns.map(([key]) => `<td>${clinicEscape(row[key] ?? '')}</td>`))
      ];
      const isExcel = preferredExportFormat === 'excel';
      const content = isExcel
        ? `<html><head><meta charset="utf-8"></head><body><table>${excelRows.map(row => `<tr>${row.join('')}</tr>`).join('')}</table></body></html>`
        : `\uFEFF${rows.map(row => row.join(',')).join('\r\n')}`;
      const blob = new Blob([content], { type: isExcel ? 'application/vnd.ms-excel;charset=utf-8' : 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      const reportName = document.getElementById('reportType').selectedOptions[0].textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-');
      const extension = isExcel ? 'xls' : 'csv';
      link.download = `${reportName}-${currentDateRange.start}-to-${currentDateRange.end}.${extension}`;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    document.getElementById('generateReportButton').addEventListener('click', generateReport);
    document.getElementById('exportReportButton').addEventListener('click', exportReportCsv);
    document.getElementById('reportPeriod').addEventListener('change', () => {
      updateReportDateControls();
      if (document.getElementById('reportPeriod').value !== 'custom') generateReport();
    });
    document.getElementById('reportType').addEventListener('change', generateReport);
    document.getElementById('reportDay').addEventListener('change', generateReport);
    document.getElementById('reportWeek').addEventListener('change', generateReport);
    document.getElementById('reportMonth').addEventListener('change', generateReport);
    document.getElementById('applyReportDateButton').addEventListener('click', generateReport);
    document.getElementById('reportStartDate').addEventListener('change', () => {
      document.getElementById('reportEndDate').min = document.getElementById('reportStartDate').value;
    });
    updateReportDateControls();
    initializeReports();
  </script>
</body>
</html>