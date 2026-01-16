<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container py-4">
  <div class="row">
    <div class="col-md-8 mx-auto">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-3">My Profile</h4>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">Please correct the errors below.</div>
          <?php endif; ?>

          <form action="<?php echo site_url('profile/update'); ?>" method="post">

            <div class="mb-3">
              <label class="form-label">Account Name</label>
              <input type="text" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                     value="<?php echo isset($input_data['name']) ? htmlspecialchars($input_data['name']) : htmlspecialchars($user->name); ?>" required>
              <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?php echo $errors['name']; ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?php echo htmlspecialchars($user->email); ?>" disabled>
            </div>

            <div class="mb-3">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_',' ',$user->role)); ?>" disabled>
            </div>

            <div class="mb-3">
              <label class="form-label">School / Office ID</label>
              <input type="text" name="school_id" class="form-control <?php echo isset($errors['school_id']) ? 'is-invalid' : ''; ?>"
                     value="<?php echo isset($input_data['school_id']) ? htmlspecialchars($input_data['school_id']) : htmlspecialchars($user->school_id); ?>" required>
              <?php if (isset($errors['school_id'])): ?><div class="invalid-feedback"><?php echo $errors['school_id']; ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" rows="3" required><?php echo isset($input_data['address']) ? htmlspecialchars($input_data['address']) : htmlspecialchars($user->school_address); ?></textarea>
              <?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?php echo $errors['address']; ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">School District</label>
              <input type="text" name="SchoolDistricts" class="form-control" value="<?php echo htmlspecialchars($user->school_district); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Level / Type</label>
              <select name="level" class="form-control <?php echo isset($errors['level']) ? 'is-invalid' : ''; ?>" required>
                <option value="" disabled <?php echo empty($user->school_level) ? 'selected' : ''; ?>>Select level</option>
                <option value="Elementary" <?php echo ($user->school_level=='Elementary') ? 'selected' : ''; ?>>Elementary</option>
                <option value="Secondary" <?php echo ($user->school_level=='Secondary') ? 'selected' : ''; ?>>Secondary</option>
                <option value="Integrated" <?php echo ($user->school_level=='Integrated') ? 'selected' : ''; ?>>Integrated</option>
                <option value="Stand Alone SHS" <?php echo ($user->school_level=='Stand Alone SHS') ? 'selected' : ''; ?>>Stand Alone SHS</option>
                <option value="Regional Division" <?php echo ($user->school_level=='Regional Division') ? 'selected' : ''; ?>>Regional Division</option>
                <option value="Provincial Division" <?php echo ($user->school_level=='Provincial Division') ? 'selected' : ''; ?>>Provincial Division</option>
                <option value="City Division" <?php echo ($user->school_level=='City Division') ? 'selected' : ''; ?>>City Division</option>
              </select>
              <?php if (isset($errors['level'])): ?><div class="invalid-feedback"><?php echo $errors['level']; ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Head / Officer Name</label>
              <input type="text" name="head_name" class="form-control <?php echo isset($errors['head_name']) ? 'is-invalid' : ''; ?>"
                     value="<?php echo isset($input_data['head_name']) ? htmlspecialchars($input_data['head_name']) : htmlspecialchars($user->school_head_name); ?>" required>
              <?php if (isset($errors['head_name'])): ?><div class="invalid-feedback"><?php echo $errors['head_name']; ?></div><?php endif; ?>
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
