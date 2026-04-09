import numpy as np

x1 = np.linspace(0, 65535, 100, endpoint=True, dtype=int)
x2 = np.linspace(65535, 0, 400, endpoint=True, dtype=int)

decay = []

for i in range(100):
	decay.append(int(x1[i]))

for i in range(400):
	decay.append(int(x2[i]))

print(decay)
